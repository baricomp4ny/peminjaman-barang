<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/peminjam/beranda')->assertRedirect('/login');
    }

    public function test_admin_bisa_buka_dashboard_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Dashboard');
    }

    public function test_admin_ditolak_di_halaman_peminjam(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/peminjam/beranda')->assertForbidden();
    }

    public function test_peminjam_bisa_buka_beranda(): void
    {
        $peminjam = User::factory()->create();

        $this->actingAs($peminjam)->get('/peminjam/beranda')->assertOk()->assertSee('Beranda');
    }

    public function test_peminjam_ditolak_di_halaman_admin(): void
    {
        $peminjam = User::factory()->create();

        $this->actingAs($peminjam)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_dashboard_mengarahkan_sesuai_peran(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertRedirect('/admin/dashboard');
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertRedirect('/peminjam/beranda');
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        $user = User::factory()->nonaktif()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_yang_dinonaktifkan_saat_login_langsung_dikeluarkan(): void
    {
        $user = User::factory()->nonaktif()->create();

        $this->actingAs($user)->get('/peminjam/beranda')->assertRedirect('/login');
        $this->assertGuest();
    }
}
