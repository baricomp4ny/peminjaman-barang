<?php

namespace Tests\Feature\Scan;

use App\Models\UnitBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

class QrLabelTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_qr_unit_berupa_svg(): void
    {
        $this->buatBarang('PRJ', 1);
        $id = UnitBarang::where('kode_unit', 'PRJ-001')->value('id');

        $res = $this->actingAs($this->admin)->get("/admin/unit/{$id}/qr");
        $res->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $res->getContent());

        $this->get("/admin/unit/{$id}/qr?unduh=1")->assertHeader('Content-Disposition', 'attachment; filename="qr-PRJ-001.svg"');
    }

    public function test_cetak_label_massal_dengan_filter_dan_pilihan(): void
    {
        $prj = $this->buatBarang('PRJ', 3, 'Proyektor');
        $this->buatBarang('LPT', 2, 'Laptop');

        $semua = $this->actingAs($this->admin)->get('/admin/unit/label');
        $semua->assertOk()->assertSee('PRJ-001')->assertSee('LPT-002')->assertSee('Cetak (5)');
        $this->assertSame(5, substr_count($semua->getContent(), '<svg'));

        $this->get('/admin/unit/label?barang_id='.$prj->id)->assertSee('PRJ-003')->assertDontSee('LPT-001');
        $pilih = UnitBarang::whereIn('kode_unit', ['PRJ-001', 'LPT-001'])->pluck('id')->map(fn ($i) => 'unit[]='.$i)->join('&');
        $this->get('/admin/unit/label?'.$pilih)->assertSee('PRJ-001')->assertSee('LPT-001')->assertDontSee('PRJ-002')->assertSee('Cetak (2)');
        $this->get('/admin/unit/label?kolom=9')->assertSessionHasErrors('kolom');
    }

    public function test_hak_akses_qr(): void
    {
        $this->buatBarang('PRJ', 1);
        $peminjam = User::factory()->create();

        $this->actingAs($peminjam)->get('/admin/unit/label')->assertForbidden();
        $this->get('/admin/unit/'.UnitBarang::value('id').'/qr')->assertForbidden();
        $this->get('/admin/scan')->assertForbidden();
    }
}
