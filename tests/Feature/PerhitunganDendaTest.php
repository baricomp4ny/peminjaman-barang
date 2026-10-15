<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\Pengaturan;
use App\Models\UnitBarang;
use App\Models\User;
use App\Services\DendaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Sprint 5 — perhitungan keterlambatan & denda (melengkapi Transaksi\SerahPengembalianTest). */
class PerhitunganDendaTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $admin;
    private User $peminjam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-10 10:00'));
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
        $this->peminjam = User::factory()->create();
        $this->buatBarang('PRJ', 3, 'Proyektor');
    }

    /** @dataProvider kalender */
    public function test_hari_terlambat_lintas_bulan_tahun_dan_kabisat(string $rencana, string $kembali, int $hari): void
    {
        $svc = app(DendaService::class);

        $this->assertSame($hari, $svc->hariTerlambat(Carbon::parse($rencana), Carbon::parse($kembali)));
        $this->assertSame(['hari' => $hari, 'denda' => $hari * 1500], $svc->hitung(Carbon::parse($rencana), Carbon::parse($kembali), 1500));
    }

    public static function kalender(): array
    {
        return [
            'lintas bulan' => ['2026-01-30', '2026-02-02 09:00', 3],
            'lintas tahun' => ['2025-12-31', '2026-01-02 16:00', 2],
            'kabisat 29 Feb' => ['2028-02-28', '2028-03-01 08:00', 2],
            'bukan kabisat' => ['2027-02-28', '2027-03-01 08:00', 1],
            'tepat waktu jam 23:59' => ['2026-10-10', '2026-10-10 23:59', 0],
            'dikembalikan lebih awal' => ['2026-10-10', '2026-10-01 10:00', 0],
            'satu bulan' => ['2026-09-10', '2026-10-10 10:00', 30],
        ];
    }

    public function test_tarif_diambil_dari_pengaturan(): void
    {
        $svc = app(DendaService::class);
        Pengaturan::atur(Pengaturan::DENDA_PER_HARI, 2500);

        $this->assertSame(2500, $svc->tarif());
        $this->assertSame(['hari' => 2, 'denda' => 5000], $svc->hitung(Carbon::parse('2026-10-08'), Carbon::parse('2026-10-10')));
    }

    private function pinjamanBerjalan(array $kode, string $mulai, string $rencana): Peminjaman
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', $mulai, $rencana, [Barang::where('kode', 'PRJ')->value('id') => count($kode)]);
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

    public function test_unit_hilang_tetap_dikenai_denda_keterlambatan(): void
    {
        $p = $this->pinjamanBerjalan(['PRJ-001'], '2026-10-05', '2026-10-08');

        $this->kembalikan($p, ['PRJ-001' => 'hilang'])->assertSessionHas('success');

        $row = PeminjamanUnit::first();
        $this->assertSame(2, $row->hari_terlambat);
        $this->assertSame(2000, $row->denda);
        $this->assertSame('hilang', UnitBarang::where('kode_unit', 'PRJ-001')->value('status'));
    }

    public function test_perkiraan_denda_berjalan_berkurang_setelah_pengembalian_sebagian(): void
    {
        $p = $this->pinjamanBerjalan(['PRJ-001', 'PRJ-002'], '2026-10-05', '2026-10-07');
        $svc = app(DendaService::class);

        // 2 unit × 3 hari × Rp1.000
        $this->assertSame(6000, $svc->perkiraan($p->load('peminjamanUnit')));
        $this->assertSame(3, $p->hariTerlambatBerjalan());

        $this->kembalikan($p, ['PRJ-001' => 'baik']);
        $this->assertSame(3000, $svc->perkiraan($p->fresh()->load('peminjamanUnit')));

        // Hari berikutnya bertambah 1 hari untuk unit yang tersisa
        $this->assertSame(4000, $svc->perkiraan($p->fresh()->load('peminjamanUnit'), Carbon::parse('2026-10-11')));
    }

    public function test_tarif_berubah_di_antara_dua_pengembalian_sebagian(): void
    {
        $p = $this->pinjamanBerjalan(['PRJ-001', 'PRJ-002'], '2026-10-05', '2026-10-08');

        $this->kembalikan($p, ['PRJ-001' => 'baik']);                 // 2 hari × 1.000
        Pengaturan::atur(Pengaturan::DENDA_PER_HARI, 3000);
        $this->travelTo(Carbon::parse('2026-10-11 09:00'));
        $this->kembalikan($p, ['PRJ-002' => 'baik'], '2026-10-11');   // 3 hari × 3.000

        $denda = PeminjamanUnit::orderBy('id')->pluck('denda')->all();
        $this->assertSame([2000, 9000], $denda);
        $this->assertSame(11000, $p->fresh()->totalDenda());
        $this->assertSame('selesai', $p->fresh()->status);
    }

    public function test_total_denda_di_laporan_sama_dengan_database(): void
    {
        $a = $this->pinjamanBerjalan(['PRJ-001'], '2026-10-01', '2026-10-05');
        $b = $this->pinjamanBerjalan(['PRJ-002', 'PRJ-003'], '2026-10-02', '2026-10-08');
        $this->kembalikan($a, ['PRJ-001' => 'baik']);                         // 5 hari → 5.000
        $this->kembalikan($b, ['PRJ-002' => 'baik', 'PRJ-003' => 'rusak']);    // 2 × 2 hari → 4.000

        $this->assertSame(9000, (int) PeminjamanUnit::sum('denda'));

        $this->actingAs($this->admin)->get('/admin/laporan/denda?dari=2026-10-01&sampai=2026-10-10')
            ->assertOk()
            ->assertSee('Rp9.000')
            ->assertSee('Jumlah unit kena denda');
    }

    public function test_pengembalian_tepat_di_hari_rencana_tidak_kena_denda(): void
    {
        $p = $this->pinjamanBerjalan(['PRJ-001'], '2026-10-08', '2026-10-10');

        $this->travelTo(Carbon::parse('2026-10-10 23:50'));
        $this->kembalikan($p, ['PRJ-001' => 'baik']);

        $this->assertSame(0, PeminjamanUnit::first()->denda);
        $this->assertFalse($p->fresh()->isTerlambat());
    }
}
