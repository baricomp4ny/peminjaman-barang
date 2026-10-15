<?php

namespace Tests\Feature\Admin;

use App\Enums\StatusUnit;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BarangUnitTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Kategori $kategori;

    private Lokasi $lokasi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->kategori = Kategori::create(['nama' => 'Elektronik']);
        $this->lokasi = Lokasi::create(['nama' => 'Gudang']);
    }

    private function barang(string $kode = 'PRJ'): Barang
    {
        return Barang::create(['kategori_id' => $this->kategori->id, 'kode' => $kode, 'nama' => 'Proyektor']);
    }

    private function tambahUnit(Barang $barang, int|string $jumlah, string $kondisi = 'baik')
    {
        return $this->actingAs($this->admin)->post('/admin/unit', [
            'barang_id' => $barang->id,
            'lokasi_id' => $this->lokasi->id,
            'kondisi' => $kondisi,
            'jumlah' => $jumlah,
        ]);
    }

    public function test_admin_bisa_tambah_barang_dengan_foto_dan_kode_jadi_kapital(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post('/admin/barang', [
            'kategori_id' => $this->kategori->id,
            'kode' => ' lpt ',
            'nama' => 'Laptop',
            'foto' => UploadedFile::fake()->image('laptop.jpg'),
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $barang = Barang::where('nama', 'Laptop')->firstOrFail();
        $this->assertSame('LPT', $barang->kode);
        Storage::disk('public')->assertExists($barang->foto);
    }

    public function test_validasi_barang_input_salah(): void
    {
        $this->barang('PRJ');

        $this->actingAs($this->admin)->post('/admin/barang', [
            'kategori_id' => 999,
            'kode' => 'PRJ',
            'nama' => '',
            'foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors(['kategori_id', 'kode', 'nama', 'foto']);

        $this->post('/admin/barang', ['kategori_id' => $this->kategori->id, 'kode' => 'P1', 'nama' => 'X'])
            ->assertSessionHasErrors('kode');
        $this->post('/admin/barang', ['kategori_id' => $this->kategori->id, 'kode' => 'ABCDEF', 'nama' => 'X'])
            ->assertSessionHasErrors('kode');
    }

    public function test_foto_lebih_dari_2mb_ditolak(): void
    {
        $this->actingAs($this->admin)->post('/admin/barang', [
            'kategori_id' => $this->kategori->id,
            'kode' => 'KMR',
            'nama' => 'Kamera',
            'foto' => UploadedFile::fake()->image('besar.jpg')->size(3000),
        ])->assertSessionHasErrors('foto');
    }

    public function test_tambah_unit_membuat_kode_otomatis_berurutan(): void
    {
        $barang = $this->barang('PRJ');

        $this->tambahUnit($barang, 3)->assertSessionHas('success');
        $this->tambahUnit($barang, 2);

        $this->assertSame(
            ['PRJ-001', 'PRJ-002', 'PRJ-003', 'PRJ-004', 'PRJ-005'],
            UnitBarang::orderBy('kode_unit')->pluck('kode_unit')->all()
        );
    }

    public function test_kode_unit_melanjutkan_nomor_terbesar_walau_ada_yang_dihapus(): void
    {
        $barang = $this->barang('PRJ');
        $this->tambahUnit($barang, 3);
        UnitBarang::where('kode_unit', 'PRJ-002')->delete();

        $this->tambahUnit($barang, 1);

        $this->assertTrue(UnitBarang::where('kode_unit', 'PRJ-004')->exists());
        $this->assertFalse(UnitBarang::where('kode_unit', 'PRJ-002')->exists());
    }

    public function test_kode_unit_lebih_dari_999_tetap_berurutan(): void
    {
        $barang = $this->barang('PRJ');
        UnitBarang::create(['barang_id' => $barang->id, 'lokasi_id' => $this->lokasi->id, 'kode_unit' => 'PRJ-999']);

        $this->tambahUnit($barang, 2);

        $this->assertTrue(UnitBarang::where('kode_unit', 'PRJ-1000')->exists());
        $this->assertTrue(UnitBarang::where('kode_unit', 'PRJ-1001')->exists());
    }

    public function test_unit_rusak_otomatis_berstatus_perbaikan(): void
    {
        $this->tambahUnit($this->barang(), 1, 'rusak');

        $this->assertSame(StatusUnit::Perbaikan, UnitBarang::first()->status);
    }

    public function test_validasi_tambah_unit_input_salah(): void
    {
        $barang = $this->barang();

        foreach ([0, 51, -1, 'abc'] as $jumlah) {
            $this->tambahUnit($barang, $jumlah)->assertSessionHasErrors('jumlah');
        }

        $this->actingAs($this->admin)->post('/admin/unit', [
            'barang_id' => 999, 'lokasi_id' => 999, 'kondisi' => 'bagus', 'jumlah' => 1,
            'tanggal_perolehan' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors(['barang_id', 'lokasi_id', 'kondisi', 'tanggal_perolehan']);

        $this->assertSame(0, UnitBarang::count());
    }

    public function test_kode_barang_terkunci_setelah_ada_unit(): void
    {
        $barang = $this->barang('PRJ');
        $this->tambahUnit($barang, 1);

        $this->put("/admin/barang/{$barang->id}", [
            'kategori_id' => $this->kategori->id, 'kode' => 'PRY', 'nama' => 'Proyektor',
        ])->assertSessionHasErrors('kode');

        $this->assertSame('PRJ', $barang->fresh()->kode);
    }

    public function test_ubah_unit_menolak_kombinasi_kondisi_dan_status_yang_salah(): void
    {
        $barang = $this->barang();
        $this->tambahUnit($barang, 1);
        $unit = UnitBarang::first();

        $this->put("/admin/unit/{$unit->id}", ['lokasi_id' => $this->lokasi->id, 'kondisi' => 'rusak', 'status' => 'tersedia'])
            ->assertSessionHasErrors('status');
        $this->put("/admin/unit/{$unit->id}", ['lokasi_id' => $this->lokasi->id, 'kondisi' => 'baik', 'status' => 'dipinjam'])
            ->assertSessionHasErrors('status');

        $this->put("/admin/unit/{$unit->id}", ['lokasi_id' => $this->lokasi->id, 'kondisi' => 'rusak', 'status' => 'perbaikan'])
            ->assertSessionHasNoErrors();
        $this->assertSame('perbaikan', $unit->fresh()->status->value);
    }

    public function test_unit_yang_sedang_dipinjam_tidak_bisa_diubah_manual(): void
    {
        $barang = $this->barang();
        $unit = UnitBarang::create([
            'barang_id' => $barang->id, 'lokasi_id' => $this->lokasi->id, 'kode_unit' => 'PRJ-001', 'status' => 'dipinjam',
        ]);

        $this->actingAs($this->admin)->put("/admin/unit/{$unit->id}", [
            'lokasi_id' => $this->lokasi->id, 'kondisi' => 'baik', 'status' => 'tersedia',
        ])->assertSessionHasErrors('status');

        $this->assertSame('dipinjam', $unit->fresh()->status->value);
    }

    public function test_unit_dan_barang_dengan_riwayat_tidak_bisa_dihapus(): void
    {
        $barang = $this->barang();
        $this->tambahUnit($barang, 2);
        [$unitDipakai, $unitBebas] = UnitBarang::orderBy('kode_unit')->get()->all();

        $peminjaman = Peminjaman::create([
            'kode_peminjaman' => 'PJM-TES-0001', 'user_id' => User::factory()->create()->id, 'tujuan' => 'Tes',
            'tanggal_pinjam' => today(), 'tanggal_rencana_kembali' => today(), 'status' => 'selesai',
        ]);
        PeminjamanUnit::create(['peminjaman_id' => $peminjaman->id, 'unit_barang_id' => $unitDipakai->id, 'diserahkan_pada' => now()]);

        $this->delete("/admin/unit/{$unitDipakai->id}")->assertSessionHas('error');
        $this->assertModelExists($unitDipakai);

        $this->delete("/admin/unit/{$unitBebas->id}")->assertSessionHas('success');
        $this->assertModelMissing($unitBebas);

        $this->delete("/admin/barang/{$barang->id}")->assertSessionHas('error');
        $this->assertModelExists($barang);
    }

    public function test_halaman_barang_dan_unit_bisa_dibuka(): void
    {
        $barang = $this->barang();
        $this->tambahUnit($barang, 2);
        $unit = UnitBarang::first();

        $this->get('/admin/barang')->assertOk()->assertSee('Proyektor');
        $this->get("/admin/barang/{$barang->id}")->assertOk()->assertSee('PRJ-001');
        $this->get('/admin/barang/create')->assertOk();
        $this->get("/admin/barang/{$barang->id}/edit")->assertOk();
        $this->get('/admin/unit')->assertOk()->assertSee('PRJ-002');
        $this->get('/admin/unit/create?barang_id='.$barang->id)->assertOk()->assertSee('PRJ-003');
        $this->get("/admin/unit/{$unit->id}/edit")->assertOk();
    }
}
