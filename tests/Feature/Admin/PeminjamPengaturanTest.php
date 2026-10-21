<?php

namespace Tests\Feature\Admin;

use App\Enums\Peran;
use App\Models\Peminjaman;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeminjamPengaturanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function dataPeminjam(array $ubah = []): array
    {
        return array_merge([
            'name' => 'Fajar Nugroho',
            'email' => 'fajar@example.com',
            'nomor_identitas' => '2201100',
            'unit' => 'Teknik Informatika',
            'no_hp' => '081234567890',
            'status' => 'aktif',
        ], $ubah);
    }

    public function test_tambah_peminjam_manual_dihapus_dan_role_tidak_bisa_diubah(): void
    {
        // Data peminjam dibuat otomatis lewat ajuan, admin tidak menambah manual
        $this->actingAs($this->admin)->get('/admin/peminjam')->assertDontSee('Tambah Peminjam');
        $this->get('/admin/peminjam/create')->assertNotFound();
        $this->post('/admin/peminjam', $this->dataPeminjam())->assertStatus(405);
        $this->assertDatabaseMissing('users', ['email' => 'fajar@example.com']);

        $peminjam = User::factory()->create();
        $this->put("/admin/peminjam/{$peminjam->id}", $this->dataPeminjam(['role' => 'admin']))->assertSessionHasNoErrors();
        $this->assertSame(Peran::Peminjam, $peminjam->fresh()->role);
    }

    public function test_validasi_peminjam_input_salah(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'nomor_identitas' => '111']);

        $p = User::factory()->create();
        $url = "/admin/peminjam/{$p->id}";
        $this->actingAs($this->admin)->put($url, $this->dataPeminjam([
            'name' => '',
            'email' => 'ada@example.com',
            'nomor_identitas' => '111',
            'no_hp' => 'abc',
            'status' => 'diblokir',
        ]))->assertSessionHasErrors(['name', 'email', 'nomor_identitas', 'no_hp', 'status']);

        $this->put($url, $this->dataPeminjam(['email' => 'bukan-email']))->assertSessionHasErrors('email');
        $this->put($url, $this->dataPeminjam(['nomor_identitas' => '']))->assertSessionHasErrors('nomor_identitas');
    }

    public function test_admin_bisa_mengubah_dan_menonaktifkan_peminjam(): void
    {
        $peminjam = User::factory()->create();

        $this->actingAs($this->admin)->put("/admin/peminjam/{$peminjam->id}", $this->dataPeminjam([
            'email' => $peminjam->email,
            'nomor_identitas' => $peminjam->nomor_identitas,
            'no_hp' => '089999999999',
            'status' => 'nonaktif',
        ]))->assertSessionHasNoErrors();

        $peminjam->refresh();
        $this->assertSame('089999999999', $peminjam->no_hp);
        $this->assertSame('nonaktif', $peminjam->status->value);
        $this->assertNull($peminjam->password);
    }

    public function test_peminjam_tidak_bisa_login_walau_data_ada(): void
    {
        $peminjam = User::factory()->create(['email' => 'fajar@example.com']);

        $this->post('/login', ['email' => 'fajar@example.com', 'password' => ''])->assertSessionHasErrors();
        $this->post('/login', ['email' => 'fajar@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNotNull($peminjam->fresh());
    }

    public function test_peminjam_dengan_riwayat_tidak_bisa_dihapus(): void
    {
        $denganRiwayat = User::factory()->create();
        Peminjaman::create([
            'kode_peminjaman' => 'PJM-TES-0001', 'user_id' => $denganRiwayat->id, 'tujuan' => 'Tes',
            'tanggal_pinjam' => today(), 'tanggal_rencana_kembali' => today(), 'status' => 'menunggu',
        ]);
        $tanpaRiwayat = User::factory()->create();

        $this->actingAs($this->admin)->delete("/admin/peminjam/{$denganRiwayat->id}")->assertSessionHas('error');
        $this->assertModelExists($denganRiwayat);

        $this->delete("/admin/peminjam/{$tanpaRiwayat->id}")->assertSessionHas('success');
        $this->assertModelMissing($tanpaRiwayat);
    }

    public function test_route_peminjam_tidak_bisa_dipakai_untuk_akun_admin(): void
    {
        $adminLain = User::factory()->admin()->create();

        $this->actingAs($this->admin)->get("/admin/peminjam/{$adminLain->id}/edit")->assertNotFound();
        $this->delete("/admin/peminjam/{$adminLain->id}")->assertNotFound();
        $this->assertModelExists($adminLain);
    }

    public function test_halaman_peminjam_bisa_dibuka(): void
    {
        $peminjam = User::factory()->create(['name' => 'Gita Permata']);

        $this->actingAs($this->admin)->get('/admin/peminjam')->assertOk()->assertSee('Gita Permata');
        $this->get('/admin/peminjam?q=Gita')->assertOk()->assertSee('Gita Permata');
        $this->get("/admin/peminjam/{$peminjam->id}")->assertOk();
        $this->get("/admin/peminjam/{$peminjam->id}/edit")->assertOk();
    }

    public function test_admin_bisa_ubah_pengaturan(): void
    {
        $this->actingAs($this->admin)->get('/admin/pengaturan/aturan')->assertOk();

        $this->put('/admin/pengaturan/aturan', [
            'lama_pinjam_maks' => 14,
            'unit_maks_per_ajuan' => 5,
            'lama_penangguhan_hari' => 0,
            'batas_ambil_hari' => 2,
            'estimasi_peninjauan_hari' => 2,
            'syarat_peminjaman' => '1. Gunakan alat dengan hati-hati.
2. Kembalikan tepat waktu.',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertEquals(14, Pengaturan::angka(Pengaturan::LAMA_PINJAM_MAKS));
        $this->assertEquals(0, Pengaturan::angka(Pengaturan::LAMA_PENANGGUHAN_HARI));
        $this->assertSame('1. Gunakan alat dengan hati-hati.
2. Kembalikan tepat waktu.', Pengaturan::teks(Pengaturan::SYARAT_PEMINJAMAN));
        $this->get('/ajuan')->assertSee('Gunakan alat dengan hati-hati.');
    }

    public function test_validasi_pengaturan_input_salah(): void
    {
        $this->actingAs($this->admin)->put('/admin/pengaturan/aturan', [
            'lama_pinjam_maks' => 0,
            'unit_maks_per_ajuan' => 'tiga',
            'lama_penangguhan_hari' => -1,
            'batas_ambil_hari' => '',
        ])->assertSessionHasErrors(['lama_pinjam_maks', 'unit_maks_per_ajuan', 'lama_penangguhan_hari', 'batas_ambil_hari']);

        // Nilai lama (default) tidak berubah
        $this->assertEquals(7, Pengaturan::angka(Pengaturan::LAMA_PINJAM_MAKS));
    }

    public function test_peminjam_tidak_bisa_akses_data_peminjam_dan_pengaturan(): void
    {
        $peminjam = User::factory()->create();

        $this->actingAs($peminjam)->get('/admin/peminjam')->assertForbidden();
        $this->put('/admin/pengaturan/aturan', ['lama_pinjam_maks' => 90])->assertForbidden();
    }
}
