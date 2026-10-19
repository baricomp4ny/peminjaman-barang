<?php

namespace Tests\Feature\Transaksi;

use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use App\Models\User;
use App\Notifications\PengembalianDicatat;
use App\Services\KeterlambatanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

class SerahPengembalianTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $admin;

    private User $peminjam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 10)->setTime(10, 0));
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
        $this->peminjam = User::factory()->create();
        $this->buatBarang('PRJ', 4, 'Proyektor');
        $this->buatBarang('LPT', 2, 'Laptop');
    }

    private function id(string $kode): string
    {
        return Barang::where('kode', $kode)->value('id');
    }

    private function serah(Peminjaman $p, array $kode)
    {
        return $this->actingAs($this->admin)->post("/admin/peminjaman/{$p->id}/serah", ['kode_unit' => $kode]);
    }

    // ---------- Serah-terima ----------

    public function test_serah_terima_lengkap_mengubah_status_menjadi_dipinjam(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-12', [$this->id('PRJ') => 2, $this->id('LPT') => 1]);

        $this->actingAs($this->admin)->get("/admin/peminjaman/{$p->id}/serah")->assertOk()->assertSee('PRJ-001');
        $this->serah($p, ['PRJ-001', 'prj-002', 'LPT-001'])->assertSessionHas('success');

        $this->assertSame('dipinjam', $p->fresh()->status->value);
        $this->assertNotNull($p->fresh()->diserahkan_pada);
        $this->assertSame(3, PeminjamanUnit::where('peminjaman_id', $p->id)->count());
        $this->assertSame(3, UnitBarang::where('status', 'dipinjam')->count());
    }

    public function test_input_manual_kode_unit_juga_diterima(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-12', [$this->id('PRJ') => 2]);

        $this->actingAs($this->admin)->post("/admin/peminjaman/{$p->id}/serah", ['kode_manual' => 'PRJ-001, PRJ-003'])
            ->assertSessionHas('success');
        $this->assertSame('dipinjam', $p->fresh()->status->value);
    }

    /** @dataProvider kasusSerahSalah */
    public function test_serah_terima_ditolak_bila_unit_tidak_cocok(array $kode, string $pesan): void
    {
        UnitBarang::where('kode_unit', 'PRJ-004')->update(['kondisi' => 'rusak', 'status' => 'perbaikan']);
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-12', [$this->id('PRJ') => 2]);

        $this->serah($p, $kode)->assertSessionHas('error', fn ($e) => str_contains($e, $pesan));

        $this->assertSame('disetujui', $p->fresh()->status->value);
        $this->assertSame(0, PeminjamanUnit::count());
        $this->assertSame(0, UnitBarang::where('status', 'dipinjam')->count());
    }

    public static function kasusSerahSalah(): array
    {
        return [
            'jenis barang tidak sesuai' => [['PRJ-001', 'LPT-001'], 'tidak termasuk'],
            'unit rusak/perbaikan' => [['PRJ-001', 'PRJ-004'], 'tidak bisa dipinjam'],
            'jumlah kurang' => [['PRJ-001'], 'belum lengkap'],
            'jumlah lebih' => [['PRJ-001', 'PRJ-002', 'PRJ-003'], 'sudah cukup'],
            'kode tidak dikenal' => [['PRJ-001', 'XYZ-999'], 'tidak dikenal'],
        ];
    }

    public function test_unit_yang_sedang_dipinjam_orang_lain_tidak_bisa_diserahkan(): void
    {
        $lain = $this->buatPeminjaman(User::factory()->create(), 'disetujui', '2026-10-09', '2026-10-11', [$this->id('PRJ') => 1]);
        $this->serahkanUnit($lain, ['PRJ-001']);
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-12', [$this->id('PRJ') => 1]);

        $this->serah($p, ['PRJ-001'])->assertSessionHas('error');
        $this->serah($p, ['PRJ-002'])->assertSessionHas('success');
    }

    public function test_serah_terima_hanya_dalam_jendela_pengambilan(): void
    {
        $belum = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-11', '2026-10-12', [$this->id('PRJ') => 1]);
        $lewat = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-08', '2026-10-12', [$this->id('LPT') => 1]);

        $this->serah($belum, ['PRJ-001'])->assertSessionHas('error', fn ($e) => str_contains($e, 'belum bisa diambil'));
        $this->serah($lewat, ['LPT-001'])->assertSessionHas('error', fn ($e) => str_contains($e, 'Batas waktu pengambilan'));
    }

    public function test_tidak_bisa_serah_terima_ajuan_yang_belum_disetujui(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-10', '2026-10-12', [$this->id('PRJ') => 1]);

        $this->serah($p, ['PRJ-001'])->assertSessionHas('error');
        $this->assertSame('menunggu', $p->fresh()->status->value);
    }

    // ---------- Pengembalian & keterlambatan ----------

    private function pinjamanBerjalan(string $mulai, string $rencana, array $kode): Peminjaman
    {
        $jumlah = collect($kode)->countBy(fn ($k) => $this->id(substr($k, 0, 3)))->all();
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', $mulai, $rencana, $jumlah);
        $this->serahkanUnit($p, $kode);

        return $p->fresh();
    }

    private function kembalikan(Peminjaman $p, array $unit, string $tanggal = '2026-10-10')
    {
        $data = [];
        foreach ($unit as $kode => $kondisi) {
            $data[UnitBarang::where('kode_unit', $kode)->value('id')] = ['pilih' => 1, 'kondisi' => $kondisi];
        }

        return $this->actingAs($this->admin)->post("/admin/pengembalian/{$p->id}", ['tanggal_kembali' => $tanggal, 'unit' => $data]);
    }

    public function test_pengembalian_tepat_waktu_dan_selesai(): void
    {
        $p = $this->pinjamanBerjalan('2026-10-08', '2026-10-10', ['PRJ-001']);

        $this->actingAs($this->admin)->get("/admin/pengembalian/{$p->id}")->assertOk();
        $this->kembalikan($p, ['PRJ-001' => 'baik'])->assertSessionHas('success');

        $this->assertSame('selesai', $p->fresh()->status->value);
        $row = PeminjamanUnit::first();
        $this->assertSame(0, $row->hari_terlambat);
        $this->assertSame(StatusUnit::Tersedia, UnitBarang::where('kode_unit', 'PRJ-001')->value('status'));
        Notification::assertSentTo($this->peminjam, PengembalianDicatat::class);
    }

    public function test_hari_terlambat_dicatat_per_unit_tanpa_denda(): void
    {
        // Rencana 7 Okt, kembali 10 Okt = terlambat 3 hari
        $p = $this->pinjamanBerjalan('2026-10-05', '2026-10-07', ['PRJ-001', 'PRJ-002']);

        $this->kembalikan($p, ['PRJ-001' => 'baik', 'PRJ-002' => 'baik'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Terlambat 3 hari') && ! str_contains($m, 'Rp'));

        $this->assertSame([3, 3], PeminjamanUnit::pluck('hari_terlambat')->all());
        $this->assertFalse(Schema::hasColumn('peminjaman_unit', 'denda'));
    }

    public function test_pengembalian_sebagian_lalu_sisanya(): void
    {
        $p = $this->pinjamanBerjalan('2026-10-08', '2026-10-12', ['PRJ-001', 'PRJ-002']);

        $this->kembalikan($p, ['PRJ-001' => 'baik'])->assertSessionHas('success');
        $this->assertSame('dipinjam', $p->fresh()->status->value);

        // Unit yang sama tidak bisa dikembalikan dua kali
        $this->kembalikan($p, ['PRJ-001' => 'baik'])->assertSessionHas('error');

        $this->kembalikan($p, ['PRJ-002' => 'baik'])->assertSessionHas('success');
        $this->assertSame('selesai', $p->fresh()->status->value);
    }

    public function test_kondisi_kembali_menentukan_status_unit(): void
    {
        $p = $this->pinjamanBerjalan('2026-10-08', '2026-10-12', ['PRJ-001', 'PRJ-002', 'PRJ-003']);

        $this->kembalikan($p, ['PRJ-001' => 'baik', 'PRJ-002' => 'rusak', 'PRJ-003' => 'hilang']);

        $unit = UnitBarang::pluck('status', 'kode_unit');
        $this->assertSame(StatusUnit::Tersedia, $unit['PRJ-001']);
        $this->assertSame(StatusUnit::Perbaikan, $unit['PRJ-002']);
        $this->assertSame(StatusUnit::Hilang, $unit['PRJ-003']);
        $this->assertSame(KondisiUnit::Rusak, UnitBarang::where('kode_unit', 'PRJ-002')->value('kondisi'));
    }

    public function test_tanggal_kembali_mundur_dihitung_sesuai_tanggal_tersebut(): void
    {
        $p = $this->pinjamanBerjalan('2026-10-05', '2026-10-07', ['PRJ-001']);

        // Barang sebenarnya kembali 8 Okt, baru dicatat 10 Okt → terlambat 1 hari
        $this->kembalikan($p, ['PRJ-001' => 'baik'], '2026-10-08');

        $this->assertSame(1, PeminjamanUnit::first()->hari_terlambat);
    }

    public function test_validasi_pengembalian_input_salah(): void
    {
        $p = $this->pinjamanBerjalan('2026-10-08', '2026-10-12', ['PRJ-001']);
        $id = UnitBarang::where('kode_unit', 'PRJ-001')->value('id');

        $this->kembalikan($p, ['PRJ-001' => 'baik'], '2026-10-11')->assertSessionHasErrors('tanggal_kembali');
        $this->kembalikan($p, ['PRJ-001' => 'baik'], '2026-10-07')->assertSessionHas('error');
        $this->actingAs($this->admin)->post("/admin/pengembalian/{$p->id}", [
            'tanggal_kembali' => '2026-10-10', 'unit' => [$id => ['pilih' => 1, 'kondisi' => 'bagus']],
        ])->assertSessionHasErrors("unit.$id.kondisi");
        $this->actingAs($this->admin)->post("/admin/pengembalian/{$p->id}", [
            'tanggal_kembali' => '2026-10-10', 'unit' => [$id => ['pilih' => 0, 'kondisi' => 'baik']],
        ])->assertSessionHas('error');

        $this->assertSame('dipinjam', $p->fresh()->status->value);
    }

    public function test_hari_terlambat_mengabaikan_jam(): void
    {
        $svc = app(KeterlambatanService::class);

        $this->assertSame(0, $svc->hariTerlambat(Carbon::parse('2026-10-10'), Carbon::parse('2026-10-10 23:59')));
        $this->assertSame(1, $svc->hariTerlambat(Carbon::parse('2026-10-10'), Carbon::parse('2026-10-11 00:01')));
        $this->assertSame(0, $svc->hariTerlambat(Carbon::parse('2026-10-10'), Carbon::parse('2026-10-08')));
    }
}
