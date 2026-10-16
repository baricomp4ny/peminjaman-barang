<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\UnitBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Katalog publik bergaya etalase: sidebar filter (cari, tanggal, kategori, lokasi, tersedia) + urutan. */
class KatalogFilterTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-10 10:00'));
    }

    /** Nama alat yang tampil di grid, sesuai urutan. */
    private function namaDi(string $url): array
    {
        return $this->get($url)->assertOk()->viewData('barang')->pluck('nama')->all();
    }

    public function test_cari_dengan_nama_atau_kode(): void
    {
        $this->buatBarang('MKR', 1, 'Mikroskop Stereo');
        $this->buatBarang('PHM', 1, 'pH Meter Digital');

        $this->assertSame(['Mikroskop Stereo'], $this->namaDi('/?q=mikro'));
        $this->assertSame(['pH Meter Digital'], $this->namaDi('/?q=PHM'));
    }

    public function test_filter_kategori_dan_lokasi(): void
    {
        $this->buatBarang('MKR', 1, 'Mikroskop Stereo');
        $phm = $this->buatBarang('PHM', 1, 'pH Meter Digital');

        $ukur = Kategori::create(['nama' => 'Alat Ukur']);
        $phm->update(['kategori_id' => $ukur->id]);
        $aseptik = Lokasi::create(['nama' => 'Ruang Tanam Aseptik']);
        UnitBarang::where('kode_unit', 'PHM-001')->update(['lokasi_id' => $aseptik->id]);

        $this->assertSame(['pH Meter Digital'], $this->namaDi('/?kategori_id='.$ukur->id));
        $this->assertSame(['pH Meter Digital'], $this->namaDi('/?lokasi_id='.$aseptik->id));
        $this->get('/?kategori_id=bukan-ulid')->assertSessionHasErrors('kategori_id');
    }

    public function test_cek_tanggal_menghitung_sisa_pada_rentang_dan_hanya_tersedia(): void
    {
        $user = User::factory()->create();
        $mkr = $this->buatBarang('MKR', 1, 'Mikroskop Stereo');
        $this->buatBarang('PHM', 1, 'pH Meter Digital');
        // Mikroskop satu-satunya sudah disetujui untuk 12–13 Okt
        $this->buatPeminjaman($user, 'disetujui', '2026-10-12', '2026-10-13', [$mkr->id => 1]);

        $hariIni = $this->get('/')->viewData('stok');
        $this->assertSame(1, $hariIni[$mkr->id]['sisa'], 'Hari ini belum bertabrakan');

        $respon = $this->get('/?mulai=2026-10-12&selesai=2026-10-12');
        $this->assertSame(0, $respon->viewData('stok')[$mkr->id]['sisa']);
        $respon->assertSee('12 Okt – 12 Okt 2026');

        $this->assertSame(['pH Meter Digital'], $this->namaDi('/?mulai=2026-10-12&selesai=2026-10-13&tersedia=1'));

        $this->get('/?mulai=2026-10-09')->assertSessionHasErrors('mulai');
        $this->get('/?mulai=2026-10-12&selesai=2026-10-11')->assertSessionHasErrors('selesai');
    }

    public function test_urutan_nama_dan_sisa_serta_paginasi(): void
    {
        $this->buatBarang('AAA', 1, 'Alat A');
        $this->buatBarang('BBB', 3, 'Alat B');
        $this->buatBarang('CCC', 2, 'Alat C');

        $this->assertSame(['Alat A', 'Alat B', 'Alat C'], $this->namaDi('/'));
        $this->assertSame(['Alat C', 'Alat B', 'Alat A'], $this->namaDi('/?urut=nama_desc'));
        $this->assertSame(['Alat B', 'Alat C', 'Alat A'], $this->namaDi('/?urut=sisa'));
        $this->get('/?urut=harga')->assertSessionHasErrors('urut');

        // 9 alat per halaman
        foreach (range(1, 8) as $i) {
            $this->buatBarang('X'.chr(64 + $i).'X', 1, 'Alat X'.$i);
        }
        $this->get('/')->assertSee('Menampilkan 1–9 dari 11 alat');
        $this->assertCount(2, $this->get('/?page=2')->viewData('barang'));
    }
}
