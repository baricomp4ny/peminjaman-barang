<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Hak akses: halaman publik untuk peminjam (tanpa login), panel /admin hanya untuk admin. */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_peminjam_bisa_dibuka_tanpa_login(): void
    {
        $this->get('/')->assertOk();
        $this->get('/ajuan')->assertOk()->assertSee('Identitas Peminjam');
        $this->get('/status')->assertOk()->assertSee('Cek Status Ajuan');
        $this->assertGuest();
    }

    public function test_tamu_diarahkan_ke_login_untuk_halaman_admin(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/admin/barang')->assertRedirect('/login');
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_admin_bisa_buka_dashboard_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Ringkasan inventaris dan transaksi aktif');
    }

    public function test_admin_login_diarahkan_ke_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/admin/dashboard');
    }

    public function test_peminjam_tidak_bisa_login(): void
    {
        $peminjam = User::factory()->create();
        // Walaupun (misalnya) punya password, peminjam tetap tidak bisa login
        $peminjam->forceFill(['password' => 'password'])->save();

        $this->post('/login', ['email' => $peminjam->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_sesi_peminjam_tidak_bisa_masuk_panel_admin(): void
    {
        // Jaga-jaga bila ada sesi user non-admin: tetap ditolak middleware role
        $peminjam = User::factory()->create();

        $this->actingAs($peminjam)->get('/admin/dashboard')->assertForbidden();
        $this->get('/profile')->assertForbidden();
    }

    public function test_registrasi_publik_dihapus(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'X', 'email' => 'x@example.com'])->assertNotFound();
    }

    public function test_admin_nonaktif_tidak_bisa_login(): void
    {
        $admin = User::factory()->admin()->nonaktif()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_yang_dinonaktifkan_saat_login_langsung_dikeluarkan(): void
    {
        $admin = User::factory()->admin()->nonaktif()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_reset_password_hanya_untuk_admin(): void
    {
        Notification::fake();
        $peminjam = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->post('/forgot-password', ['email' => $peminjam->email])->assertSessionHasErrors('email');
        $this->post('/forgot-password', ['email' => $admin->email])->assertSessionHas('status');

        Notification::assertNotSentTo($peminjam, ResetPassword::class);
        Notification::assertSentTo($admin, ResetPassword::class);
    }
}
