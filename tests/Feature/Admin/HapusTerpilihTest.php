<?php

namespace Tests\Feature\Admin;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\UnitBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Pilih banyak + hapus terpilih: yang boleh dihapus terhapus, yang dilindungi dilewati dengan alasan. */
class HapusTerpilihTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
    }

    public function test_keenam_halaman_punya_checkbox_dan_bilah_hapus(): void
    {
        $this->buatBarang('PRJ', 1);
        User::factory()->create();

        foreach (['barang', 'unit', 'kategori', 'lokasi', 'peminjam', 'pengaturan/admin'] as $hal) {
            $this->actingAs($this->admin)->get("/admin/{$hal}")->assertOk()
                ->assertSee('pilihBanyak', false)
                ->assertSee('Hapus terpilih')
                ->assertSee('Pilih semua di halaman ini', false);
        }
    }

    public function test_unit_terhapus_kecuali_yang_pernah_dipinjam(): void
    {
        $b = $this->buatBarang('PRJ', 3);
        $p = $this->buatPeminjaman(User::factory()->create(), 'disetujui', today(), today()->addDay(), [$b->id => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);
        $id = UnitBarang::pluck('id', 'kode_unit');

        $this->actingAs($this->admin)->delete('/admin/unit/hapus-terpilih', ['id' => $id->values()->all()])
            ->assertSessionHas('success', '2 unit berhasil dihapus. 1 dilewati: PRJ-001 (tercatat di riwayat peminjaman).');

        $this->assertSame(['PRJ-001'], UnitBarang::pluck('kode_unit')->all());
    }

    public function test_kategori_lokasi_barang_dilewati_bila_masih_dipakai(): void
    {
        $this->buatBarang('PRJ', 1); // kategori "Umum" & lokasi "Gudang" dipakai
        $kosongK = Kategori::create(['nama' => 'Kosong']);
        $kosongL = Lokasi::create(['nama' => 'Rak Kosong']);
        $tanpaUnit = Barang::create(['kategori_id' => $kosongK->id, 'kode' => 'XYZ', 'nama' => 'Tanpa Unit']);

        $this->actingAs($this->admin)->delete('/admin/barang/hapus-terpilih', ['id' => Barang::pluck('id')->all()])
            ->assertSessionHas('success', fn ($p) => str_starts_with($p, '1 jenis barang berhasil dihapus. 1 dilewati: Barang PRJ (masih memiliki unit)'));
        $this->assertModelMissing($tanpaUnit);

        $this->delete('/admin/kategori/hapus-terpilih', ['id' => Kategori::pluck('id')->all()])->assertSessionHas('success');
        $this->assertModelMissing($kosongK);
        $this->assertDatabaseHas('kategori', ['nama' => 'Umum']);

        $this->delete('/admin/lokasi/hapus-terpilih', ['id' => Lokasi::pluck('id')->all()])->assertSessionHas('success');
        $this->assertModelMissing($kosongL);
        $this->assertDatabaseHas('lokasi', ['nama' => 'Gudang']);
    }

    public function test_peminjam_dan_admin_dilindungi(): void
    {
        $b = $this->buatBarang('PRJ', 1);
        $punyaRiwayat = User::factory()->create(['name' => 'Rina']);
        $this->buatPeminjaman($punyaRiwayat, 'menunggu', today(), today()->addDay(), [$b->id => 1]);
        $bersih = User::factory()->create();

        // Id admin yang ikut terkirim ke endpoint peminjam tidak tersentuh
        $this->actingAs($this->admin)->delete('/admin/peminjam/hapus-terpilih', ['id' => [$punyaRiwayat->id, $bersih->id, $this->admin->id]])
            ->assertSessionHas('success', '1 peminjam berhasil dihapus. 1 dilewati: Rina (memiliki riwayat peminjaman).');
        $this->assertModelMissing($bersih);
        $this->assertModelExists($this->admin);

        $adminLain = User::factory()->admin()->create(['name' => 'Admin Lain']);
        $this->delete('/admin/pengaturan/admin/hapus-terpilih', ['id' => [$this->admin->id, $adminLain->id]])
            ->assertSessionHas('success', '1 akun admin berhasil dihapus. 1 dilewati: Admin Utama (akun Anda sendiri).');
        $this->assertModelMissing($adminLain);
    }

    public function test_validasi_dan_hanya_admin(): void
    {
        $this->actingAs($this->admin)->delete('/admin/unit/hapus-terpilih', [])->assertSessionHasErrors('id');
        $this->delete('/admin/unit/hapus-terpilih', ['id' => ['bukan-ulid']])->assertSessionHasErrors('id.0');
        $this->delete('/admin/unit/hapus-terpilih', ['id' => array_fill(0, 101, '01ARZ3NDEKTSV4RRFFQ69G5FAV')])->assertSessionHasErrors('id');

        $u = $this->buatBarang('PRJ', 1)->unit()->first();
        $this->actingAs(User::factory()->create())->delete('/admin/unit/hapus-terpilih', ['id' => [$u->id]])->assertForbidden();
        $this->assertModelExists($u);
    }
}
