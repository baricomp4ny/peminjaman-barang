<?php

namespace Tests\Feature;

use App\Models\PeminjamanUnit;
use App\Models\Pengaturan;
use App\Models\User;
use App\Notifications\PengingatJatuhTempo;
use App\Notifications\PengingatPengembalian;
use App\Notifications\PengingatTerlambat;
use App\Services\KeterlambatanService;
use App\Services\LaporanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Pengganti denda: hari terlambat, penangguhan pengajuan, pengingat, statistik pemakaian. */
class KeterlambatanPenangguhanTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $admin;

    private User $peminjam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-15 10:00'));
        $this->admin = User::factory()->admin()->create();
        $this->peminjam = User::factory()->create(['nomor_identitas' => 'J0301209999', 'email' => 'rina@contoh.test']);
        $this->buatBarang('PRJ', 3, 'Proyektor');
    }

    /** Buat pinjaman yang unitnya dikembalikan pada tanggal tertentu. */
    private function kembaliPada(string $rencana, string $kembali): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', Carbon::parse($rencana)->subDay(), $rencana, [$this->barangId() => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);
        PeminjamanUnit::where('peminjaman_id', $p->id)->update([
            'dikembalikan_pada' => $kembali.' 10:00',
            'kondisi_kembali' => 'baik',
            'hari_terlambat' => app(KeterlambatanService::class)->hariTerlambat(Carbon::parse($rencana), Carbon::parse($kembali)),
        ]);
        $p->update(['status' => 'selesai']);
    }

    private function barangId(): string
    {
        return \App\Models\Barang::where('kode', 'PRJ')->value('id');
    }

    private function ajukan(): \Illuminate\Testing\TestResponse
    {
        return $this->post('/ajuan', [
            'name' => $this->peminjam->name, 'nomor_identitas' => 'J0301209999', 'email' => 'rina@contoh.test', 'no_hp' => '081234567890',
            'tujuan' => 'Praktikum kultur jaringan', 'tanggal_pinjam' => '2026-10-20', 'tanggal_rencana_kembali' => '2026-10-21',
            'items' => [['barang_id' => $this->barangId(), 'jumlah' => 1]],
        ]);
    }

    /** @dataProvider kalender */
    public function test_hari_terlambat_lintas_bulan_tahun_dan_kabisat(string $rencana, string $kembali, int $hari): void
    {
        $this->assertSame($hari, app(KeterlambatanService::class)->hariTerlambat(Carbon::parse($rencana), Carbon::parse($kembali)));
    }

    public static function kalender(): array
    {
        return [
            'lintas bulan' => ['2026-01-30', '2026-02-02 09:00', 3],
            'lintas tahun' => ['2025-12-31', '2026-01-02 16:00', 2],
            'kabisat 29 Feb' => ['2028-02-28', '2028-03-01 08:00', 2],
            'tepat waktu jam 23:59' => ['2026-10-10', '2026-10-10 23:59', 0],
            'dikembalikan lebih awal' => ['2026-10-10', '2026-10-01 10:00', 0],
        ];
    }

    public function test_penangguhan_dihitung_sejak_tanggal_kembali_terlambat(): void
    {
        // Kembali terlambat 12 Okt + 7 hari → boleh mengajukan mulai 19 Okt
        $this->kembaliPada('2026-10-10', '2026-10-12');
        $svc = app(KeterlambatanService::class);

        $this->assertSame('2026-10-19', $svc->penangguhanBerakhir($this->peminjam)->toDateString());
        $this->ajukan()->assertSessionHasErrors(['nomor_identitas' => 'Anda mengembalikan alat terlambat pada 12 Okt 2026. Sesuai aturan penangguhan 7 hari, pengajuan baru dapat dilakukan mulai 19 Okt 2026.']);

        $this->travelTo(Carbon::parse('2026-10-19 08:00'));
        $this->assertNull($svc->penangguhanBerakhir($this->peminjam));
        $this->ajukan()->assertSessionHasNoErrors();
    }

    public function test_penangguhan_nol_berarti_tanpa_penangguhan_dan_kembali_tepat_waktu_tidak_ditangguhkan(): void
    {
        $this->kembaliPada('2026-10-14', '2026-10-14');
        $this->assertNull(app(KeterlambatanService::class)->penangguhanBerakhir($this->peminjam));

        $this->kembaliPada('2026-10-10', '2026-10-13');
        Pengaturan::atur(Pengaturan::LAMA_PENANGGUHAN_HARI, 0);
        $this->assertNull(app(KeterlambatanService::class)->penangguhanBerakhir($this->peminjam));
        $this->ajukan()->assertSessionHasNoErrors();
    }

    public function test_jumlah_keterlambatan_tampil_di_data_peminjam_dan_panel_verifikasi(): void
    {
        $this->kembaliPada('2026-10-01', '2026-10-03');
        $this->kembaliPada('2026-10-05', '2026-10-09');
        $this->kembaliPada('2026-10-11', '2026-10-11'); // tepat waktu, tidak dihitung

        $this->assertSame(2, User::whereKey($this->peminjam->id)->withJumlahTerlambat()->value('jumlah_terlambat'));
        $this->actingAs($this->admin)->get('/admin/peminjam')->assertSee('2×');
        $this->get("/admin/peminjam/{$this->peminjam->id}")->assertSee('2× terlambat')->assertSee('Penangguhan');

        $ajuan = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-20', '2026-10-21', [$this->barangId() => 1]);
        $this->get("/admin/peminjaman/{$ajuan->id}")->assertSee('Riwayat terlambat 2×')->assertSee('pengajuan baru dapat dilakukan mulai 16 Okt 2026');
    }

    public function test_pengingat_h1_hari_h_dan_setiap_hari_terlambat_tanpa_dobel(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-13', '2026-10-16', [$this->barangId() => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);
        $kirim = fn (string $tgl) => $this->artisan('peminjaman:pengingat', ['--tanggal' => $tgl])->assertSuccessful();

        $kirim('2026-10-15');
        \Illuminate\Support\Facades\Notification::assertSentTo($this->peminjam, PengingatPengembalian::class);
        $kirim('2026-10-16');
        \Illuminate\Support\Facades\Notification::assertSentTo($this->peminjam, PengingatJatuhTempo::class);
        $kirim('2026-10-17');
        $kirim('2026-10-18');
        \Illuminate\Support\Facades\Notification::assertSentToTimes($this->peminjam, PengingatTerlambat::class, 2);
        \Illuminate\Support\Facades\Notification::assertSentTo($this->peminjam, PengingatTerlambat::class,
            fn ($n) => $n->hari === 2 && ! str_contains($n->pesan(), 'denda') && str_contains($n->pesan(), 'terlambat 2 hari'));
    }

    public function test_pengingat_terlambat_tidak_dobel_di_hari_yang_sama(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-12', [$this->barangId() => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);

        $this->artisan('peminjaman:pengingat')->expectsOutputToContain('1 pengingat')->assertSuccessful();
        $this->artisan('peminjaman:pengingat')->expectsOutputToContain('0 pengingat')->assertSuccessful();
        $this->assertSame(1, $this->peminjam->notifications()->where('type', PengingatTerlambat::class)->count());
    }

    public function test_laporan_statistik_paling_sering_dan_paling_jarang(): void
    {
        $this->buatBarang('MKR', 2, 'Mikroskop');
        $this->buatBarang('PHM', 1, 'pH Meter');
        $mkr = \App\Models\Barang::where('kode', 'MKR')->value('id');

        foreach ([['2026-10-02', ['PRJ-001', 'PRJ-002']], ['2026-10-05', ['PRJ-003']], ['2026-10-06', ['MKR-001']]] as [$tgl, $unit]) {
            $jumlah = collect($unit)->countBy(fn ($k) => str_starts_with($k, 'PRJ') ? $this->barangId() : $mkr)->all();
            $p = $this->buatPeminjaman($this->peminjam, 'disetujui', $tgl, Carbon::parse($tgl)->addDay(), $jumlah);
            $this->serahkanUnit($p, $unit);
        }

        $laporan = app(LaporanService::class)->buat('statistik', Carbon::parse('2026-10-01'), Carbon::parse('2026-10-10'));

        $this->assertSame(['Proyektor', 'Mikroskop', 'pH Meter'], array_column($laporan['baris'], 2));
        $this->assertSame([2, 3], [$laporan['baris'][0][4], $laporan['baris'][0][5]]);
        $this->assertSame('Tidak pernah dipinjam', $laporan['baris'][2][6]);
        $this->assertSame(3, $laporan['ringkasan']['Total transaksi']);
        $this->assertStringStartsWith('pH Meter', $laporan['ringkasan']['Paling jarang dipinjam']);

        $this->actingAs($this->admin)->get('/admin/laporan/statistik?dari=2026-10-01&sampai=2026-10-10')->assertOk()->assertSee('Statistik Pemakaian');
        $this->get('/admin/laporan/statistik?dari=2026-10-01&sampai=2026-10-10&format=pdf')->assertOk();
        $this->get('/admin/laporan/statistik?dari=2026-10-01&sampai=2026-10-10&format=excel')->assertOk();
        $this->get('/admin/laporan/denda')->assertNotFound();
    }
}
