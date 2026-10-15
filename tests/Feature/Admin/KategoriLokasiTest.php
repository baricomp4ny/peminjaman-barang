<?php

namespace Tests\Feature\Admin;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\UnitBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriLokasiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_bisa_tambah_ubah_hapus_kategori(): void
    {
        $this->actingAs($this->admin)->get('/admin/kategori')->assertOk();

        $this->post('/admin/kategori', ['nama' => 'Elektronik'])
            ->assertRedirect('/admin/kategori')->assertSessionHas('success');
        $kategori = Kategori::where('nama', 'Elektronik')->firstOrFail();

        $this->put("/admin/kategori/{$kategori->id}", ['nama' => 'Elektronika'])->assertSessionHas('success');
        $this->assertSame('Elektronika', $kategori->fresh()->nama);

        $this->delete("/admin/kategori/{$kategori->id}")->assertSessionHas('success');
        $this->assertModelMissing($kategori);
    }

    public function test_nama_kategori_wajib_dan_unik(): void
    {
        Kategori::create(['nama' => 'Fotografi']);

        $this->actingAs($this->admin)->post('/admin/kategori', ['nama' => ''])->assertSessionHasErrors('nama');
        $this->post('/admin/kategori', ['nama' => 'Fotografi'])->assertSessionHasErrors('nama');
        $this->post('/admin/kategori', ['nama' => str_repeat('a', 101)])->assertSessionHasErrors('nama');
        $this->assertSame(1, Kategori::count());
    }

    public function test_simpan_ulang_kategori_dengan_nama_sendiri_tidak_dianggap_duplikat(): void
    {
        $kategori = Kategori::create(['nama' => 'Fotografi']);

        $this->actingAs($this->admin)->put("/admin/kategori/{$kategori->id}", ['nama' => 'Fotografi'])
            ->assertSessionHasNoErrors();
    }

    public function test_kategori_yang_dipakai_barang_tidak_bisa_dihapus(): void
    {
        $kategori = Kategori::create(['nama' => 'Elektronik']);
        Barang::create(['kategori_id' => $kategori->id, 'kode' => 'PRJ', 'nama' => 'Proyektor']);

        $this->actingAs($this->admin)->delete("/admin/kategori/{$kategori->id}")->assertSessionHas('error');
        $this->assertModelExists($kategori);
    }

    public function test_admin_bisa_kelola_lokasi_dan_lokasi_terpakai_tidak_bisa_dihapus(): void
    {
        $this->actingAs($this->admin)->post('/admin/lokasi', ['nama' => 'Gudang', 'keterangan' => 'Lantai 1'])
            ->assertSessionHas('success');
        $lokasi = Lokasi::where('nama', 'Gudang')->firstOrFail();

        $this->post('/admin/lokasi', ['nama' => 'Gudang'])->assertSessionHasErrors('nama');

        $kategori = Kategori::create(['nama' => 'Elektronik']);
        $barang = Barang::create(['kategori_id' => $kategori->id, 'kode' => 'PRJ', 'nama' => 'Proyektor']);
        UnitBarang::create(['barang_id' => $barang->id, 'lokasi_id' => $lokasi->id, 'kode_unit' => 'PRJ-001']);

        $this->delete("/admin/lokasi/{$lokasi->id}")->assertSessionHas('error');
        $this->assertModelExists($lokasi);
    }

    public function test_peminjam_tidak_bisa_akses_data_master(): void
    {
        $peminjam = User::factory()->create();

        $this->actingAs($peminjam)->get('/admin/kategori')->assertForbidden();
        $this->post('/admin/kategori', ['nama' => 'Coba'])->assertForbidden();
        $this->get('/admin/lokasi')->assertForbidden();
        $this->assertSame(0, Kategori::count());
    }
}
