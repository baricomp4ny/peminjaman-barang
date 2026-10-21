<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Tombol WhatsApp pengingat untuk peminjam yang terlambat. */
class TombolWhatsAppTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    public function test_format_nomor_whatsapp(): void
    {
        $this->assertSame('6281234567890', WhatsApp::nomor('0812-3456-7890'));
        $this->assertSame('6281234567890', WhatsApp::nomor('+62 812 3456 7890'));
        $this->assertSame('6281234567890', WhatsApp::nomor('81234567890'));
        $this->assertNull(WhatsApp::nomor(null));
        $this->assertNull(WhatsApp::nomor('0251-123'));      // bukan nomor HP
        $this->assertNull(WhatsApp::nomor('abc'));
    }

    public function test_tombol_hanya_untuk_peminjaman_terlambat_dengan_nomor_valid(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 09:00'));
        $admin = User::factory()->admin()->create();
        $rina = User::factory()->create(['name' => 'Rina', 'no_hp' => '081234567890']);
        $tanpaHp = User::factory()->create(['name' => 'Budi', 'no_hp' => null]);
        $b = $this->buatBarang('PRJ', 4, 'Proyektor');

        $terlambat = $this->buatPeminjaman($rina, 'disetujui', '2026-10-08', '2026-10-12', [$b->id => 1]);
        $this->serahkanUnit($terlambat, ['PRJ-001'], '2026-10-08 08:00');
        $lain = $this->buatPeminjaman($tanpaHp, 'disetujui', '2026-10-08', '2026-10-12', [$b->id => 1]);
        $this->serahkanUnit($lain, ['PRJ-002'], '2026-10-08 08:00');
        $tepat = $this->buatPeminjaman($rina, 'disetujui', '2026-10-14', '2026-10-16', [$b->id => 1]);
        $this->serahkanUnit($tepat, ['PRJ-003'], '2026-10-14 08:00');

        $html = $this->actingAs($admin)->get('/admin/peminjaman?tab=dipinjam')->assertOk()->getContent();
        // Hanya satu tombol: Rina terlambat; Budi tanpa nomor; pinjaman Rina lainnya belum terlambat
        $this->assertSame(1, substr_count($html, 'https://wa.me/6281234567890?text='));

        $this->get(route('admin.peminjaman.show', $terlambat))
            ->assertSee('https://wa.me/6281234567890?text=', false)
            ->assertSee(rawurlencode($terlambat->kode_peminjaman), false)
            ->assertSee(rawurlencode('Terlambat: 3 hari'), false);
        $this->get(route('admin.peminjaman.show', $tepat))->assertDontSee('wa.me', false);
    }
}
