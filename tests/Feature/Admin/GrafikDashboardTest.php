<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Data grafik Dashboard: peminjaman per bulan & 5 alat terpopuler (6 bulan terakhir, berdasarkan serah-terima). */
class GrafikDashboardTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    public function test_grafik_menghitung_berdasarkan_serah_terima_6_bulan(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 09:00'));
        $admin = User::factory()->admin()->create();
        $rina = User::factory()->create();
        $prj = $this->buatBarang('PRJ', 3, 'Proyektor');
        $mkr = $this->buatBarang('MKR', 3, 'Mikroskop');

        $p = $this->buatPeminjaman($rina, 'disetujui', '2026-10-01', '2026-10-03', [$prj->id => 2]);
        $this->serahkanUnit($p, ['PRJ-001', 'PRJ-002'], '2026-10-01 08:00');
        $p = $this->buatPeminjaman($rina, 'disetujui', '2026-08-05', '2026-08-06', [$mkr->id => 1]);
        $this->serahkanUnit($p, ['MKR-001'], '2026-08-05 08:00');
        // Di luar 6 bulan (Mar 2026) dan ajuan yang belum diserahkan tidak dihitung
        $p = $this->buatPeminjaman($rina, 'disetujui', '2026-03-02', '2026-03-03', [$mkr->id => 1]);
        $this->serahkanUnit($p, ['MKR-002'], '2026-03-02 08:00');
        $this->buatPeminjaman($rina, 'menunggu', '2026-10-20', '2026-10-21', [$mkr->id => 3]);

        $grafik = $this->actingAs($admin)->get('/admin/dashboard')->assertOk()
            ->assertSee('Peminjaman per bulan')->assertSee('5 alat terpopuler')
            ->viewData('grafik');

        $this->assertSame(['Mei 2026', 'Jun 2026', 'Jul 2026', 'Agt 2026', 'Sep 2026', 'Okt 2026'], $grafik['bulan']['label']);
        $this->assertSame([0, 0, 0, 1, 0, 1], $grafik['bulan']['nilai']);
        $this->assertSame(['Proyektor', 'Mikroskop'], $grafik['populer']['label']);
        $this->assertSame([2, 1], $grafik['populer']['nilai']);
    }
}
