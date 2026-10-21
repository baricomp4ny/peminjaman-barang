<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\KeterlambatanService;
use App\Services\LaporanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Unduh PDF/Excel dari Riwayat (pengganti Laporan Transaksi) mengikuti filter yang dipakai. */
class RiwayatUnduhTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    public function test_unduhan_riwayat_mengikuti_filter_dan_menu_lebih_ringkas(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 10:00'));
        $admin = User::factory()->admin()->create();
        $rina = User::factory()->create(['name' => 'Rina Kartika']);
        $budi = User::factory()->create(['name' => 'Budi Santoso']);
        $b = $this->buatBarang('PRJ', 3, 'Proyektor');
        $this->buatPeminjaman($rina, 'selesai', '2026-10-05', '2026-10-06', [$b->id => 1]);
        $this->buatPeminjaman($budi, 'ditolak', '2026-10-07', '2026-10-08', [$b->id => 1]);

        $this->actingAs($admin)->get('/admin/riwayat')->assertOk()
            ->assertSee('Unduh PDF')->assertSee('Unduh Excel')
            // Menu Transaksi tanpa "Pengembalian", menu Laporan tanpa "Transaksi"
            ->assertDontSee('admin/pengembalian"', false)
            ->assertDontSee('admin/laporan/peminjaman', false);

        $pdf = $this->get('/admin/riwayat?status=selesai&format=pdf')->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->get('/admin/riwayat?format=excel')->assertOk()->assertDownload();
        $this->get('/admin/riwayat?format=word')->assertSessionHasErrors('format');

        // Isi laporan sama dengan filter: status selesai → hanya Rina
        $laporan = null;
        $this->mock(LaporanService::class, function ($mock) use (&$laporan) {
            $mock->shouldReceive('riwayat')->andReturnUsing(function ($data, $periode) use (&$laporan) {
                $laporan = (new LaporanService(new KeterlambatanService))->riwayat($data, $periode);

                return $laporan;
            });
            $mock->shouldReceive('unduh')->andReturn(response('ok'));
        });
        $this->get('/admin/riwayat?status=selesai&format=pdf')->assertOk();
        $this->assertSame(['Rina Kartika'], array_column($laporan['baris'], 2));
        $this->assertStringContainsString('status Selesai', $laporan['periode']);

        $this->actingAs($rina)->get('/admin/riwayat?format=pdf')->assertForbidden();
    }
}
