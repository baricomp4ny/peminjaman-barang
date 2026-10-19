<?php

namespace Tests\Feature\Admin;

use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Pengaturan > Data Admin. */
class AkunAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
    }

    private function data(array $ubah = []): array
    {
        return array_merge([
            'name' => 'Petugas Baru',
            'email' => 'petugas@example.com',
            'no_hp' => '081234567890',
            'status' => 'aktif',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $ubah);
    }

    public function test_tambah_admin_baru_bisa_login(): void
    {
        $this->actingAs($this->admin)->get('/admin/pengaturan/admin')->assertOk()->assertSee('Admin Utama');
        $this->post('/admin/pengaturan/admin', $this->data(['role' => 'peminjam']))->assertSessionHasNoErrors()->assertSessionHas('success');

        $baru = User::where('email', 'petugas@example.com')->firstOrFail();
        $this->assertSame('admin', $baru->role->value);
        $this->assertTrue(Hash::check('rahasia123', $baru->password));

        auth()->logout();
        $this->post('/login', ['email' => 'petugas@example.com', 'password' => 'rahasia123'])->assertRedirect('/dashboard');
    }

    public function test_validasi_admin_input_salah(): void
    {
        $this->actingAs($this->admin)->post('/admin/pengaturan/admin', $this->data([
            'name' => '', 'email' => $this->admin->email, 'no_hp' => 'abc', 'status' => 'x',
            'password' => '123', 'password_confirmation' => '456',
        ]))->assertSessionHasErrors(['name', 'email', 'no_hp', 'status', 'password']);
    }

    public function test_ubah_tanpa_password_dan_reset_password(): void
    {
        $lain = User::factory()->admin()->create();
        $hashLama = $lain->password;

        $this->actingAs($this->admin)->put("/admin/pengaturan/admin/{$lain->id}", $this->data([
            'email' => $lain->email, 'password' => '', 'password_confirmation' => '',
        ]))->assertSessionHasNoErrors();
        $this->assertSame($hashLama, $lain->fresh()->password);

        $this->put("/admin/pengaturan/admin/{$lain->id}", $this->data(['email' => $lain->email, 'password' => 'baru12345', 'password_confirmation' => 'baru12345']));
        $this->assertTrue(Hash::check('baru12345', $lain->fresh()->password));
    }

    public function test_tidak_bisa_menonaktifkan_atau_menghapus_diri_sendiri(): void
    {
        $this->actingAs($this->admin)->put("/admin/pengaturan/admin/{$this->admin->id}", $this->data([
            'email' => $this->admin->email, 'status' => 'nonaktif', 'password' => '', 'password_confirmation' => '',
        ]))->assertSessionHasErrors('status');

        $this->delete("/admin/pengaturan/admin/{$this->admin->id}")->assertSessionHas('error');
        $this->assertSame('aktif', $this->admin->fresh()->status->value);
    }

    public function test_admin_yang_pernah_memverifikasi_tidak_bisa_dihapus(): void
    {
        $lain = User::factory()->admin()->create();
        Peminjaman::create([
            'kode_peminjaman' => 'PJM-TES-0001', 'user_id' => User::factory()->create()->id, 'tujuan' => 'Tes',
            'tanggal_pinjam' => today(), 'tanggal_rencana_kembali' => today(), 'status' => 'ditolak',
            'alasan_penolakan' => 'Tes alasan', 'diverifikasi_oleh' => $lain->id, 'diverifikasi_pada' => now(),
        ]);
        $bebas = User::factory()->admin()->create();

        $this->actingAs($this->admin)->delete("/admin/pengaturan/admin/{$lain->id}")->assertSessionHas('error');
        $this->assertModelExists($lain);

        $this->delete("/admin/pengaturan/admin/{$bebas->id}")->assertSessionHas('success');
        $this->assertModelMissing($bebas);
    }

    public function test_route_data_admin_tidak_bisa_dipakai_untuk_peminjam(): void
    {
        $peminjam = User::factory()->create();

        $this->actingAs($this->admin)->get("/admin/pengaturan/admin/{$peminjam->id}/edit")->assertNotFound();
        $this->delete("/admin/pengaturan/admin/{$peminjam->id}")->assertNotFound();
        $this->assertModelExists($peminjam);
    }

    public function test_menu_dan_subjudul_pengaturan(): void
    {
        $this->actingAs($this->admin)->get('/admin/pengaturan')->assertRedirect('/admin/pengaturan/aturan');
        $this->get('/admin/pengaturan/aturan')->assertOk()
            ->assertSee('Pengelolaan data admin dan aturan peminjaman')
            ->assertSee('Data Admin')->assertSee('Aturan Peminjaman')->assertDontSee('Denda');
    }
}
