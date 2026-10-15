<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'nomor_identitas' => '2209999',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_registration_always_creates_peminjam_even_if_role_is_sent(): void
    {
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'nomor_identitas' => '2209998',
            'role' => 'admin',
            'status' => 'nonaktif',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'penyusup@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_PEMINJAM, $user->role);
        $this->assertSame(User::STATUS_AKTIF, $user->status);
    }

    public function test_nomor_identitas_is_required(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('nomor_identitas');

        $this->assertGuest();
    }
}
