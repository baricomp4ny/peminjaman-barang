<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use App\Models\User;
use App\Services\JadwalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Jadwal Pemakaian Alat: timeline per unit/alat + ringkasan; versi publik tanpa identitas. */
class JadwalPemakaianTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $peminjam;

    private Barang $barang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-15 10:00'));
        $this->peminjam = User::factory()->create(['name' => 'Rina Kartika']);
        $this->barang = $this->buatBarang('PRJ', 3, 'Proyektor');
    }

    private function susun(bool $publik, array $filter = []): array
    {
        return app(JadwalService::class)->susun(Carbon::parse('2026-10-14'), $filter, $publik);
    }

    /** Blok milik unit dengan kode tertentu. */
    private function blok(array $data, string $kode): array
    {
        return collect($data['perUnit'])->first(fn ($b) => $b['unit']->kode_unit === $kode)['blok'];
    }

    public function test_unit_dipinjam_terlambat_dan_ajuan_ditempatkan_di_timeline(): void
    {
        // Dipinjam 13–16 Okt (sebagian di luar jendela, mulai 14 Okt)
        $p1 = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-13', '2026-10-16', [$this->barang->id => 1]);
        $this->serahkanUnit($p1, ['PRJ-001']);
        // Terlambat: rencana 12 Okt belum kembali → merah sampai hari ini (15 Okt)
        $p2 = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-12', [$this->barang->id => 1]);
        $this->serahkanUnit($p2, ['PRJ-002']);
        // Disetujui 20–21 Okt & menunggu 20–21 Okt → ditempatkan ke unit kosong pertama
        $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-20', '2026-10-21', [$this->barang->id => 1]);
        $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-20', '2026-10-21', [$this->barang->id => 1]);

        $admin = $this->susun(false);

        // PRJ-001: dipinjam 14–16 Okt (kolom 0–2) lalu dipesan 20–21 Okt (kolom 6–7)
        $this->assertSame([['dipinjam', 0, 2], ['disetujui', 6, 7]], array_map(fn ($b) => [$b['jenis'], $b['kolomAwal'], $b['kolomAkhir']], $this->blok($admin, 'PRJ-001')));
        $this->assertSame('terlambat', $this->blok($admin, 'PRJ-002')[0]['jenis']);
        $this->assertSame(1, $this->blok($admin, 'PRJ-002')[0]['kolomAkhir'], 'Terlambat ditampilkan sampai hari ini');

        // PRJ-001 kosong mulai 17 Okt → menerima ajuan disetujui; ajuan menunggu ke unit berikutnya yang kosong
        $jenis = collect($admin['perUnit'])->flatMap(fn ($b) => collect($b['blok'])->map(fn ($x) => $b['unit']->kode_unit.':'.$x['jenis']))->all();
        $this->assertContains('PRJ-001:disetujui', $jenis);
        $this->assertContains('PRJ-002:menunggu', $jenis);
        $this->assertStringContainsString('Rina Kartika', $this->blok($admin, 'PRJ-001')[0]['judul']);
        $this->assertNotNull($this->blok($admin, 'PRJ-001')[0]['url']);
    }

    public function test_versi_publik_tanpa_identitas_dan_tanpa_ajuan_menunggu(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-14', '2026-10-16', [$this->barang->id => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);
        $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-20', '2026-10-21', [$this->barang->id => 1]);

        $publik = $this->susun(true);
        $semua = collect($publik['perUnit'])->flatMap(fn ($b) => $b['blok']);

        $this->assertCount(1, $semua, 'Ajuan menunggu tidak tampil untuk publik');
        $this->assertSame('Dipinjam', $semua[0]['judul']);
        $this->assertNull($semua[0]['url']);

        $html = $this->get('/jadwal?mulai=2026-10-14')->assertOk()->assertSee('Jadwal Pemakaian Alat')->getContent();
        $this->assertStringNotContainsString('Rina Kartika', $html);
        $this->assertStringNotContainsString($p->kode_peminjaman, $html);
        $this->assertStringNotContainsString('Menunggu verifikasi', $html);
        // Sel kosong mengarah ke form ajuan dengan alat & tanggal terisi
        $this->assertStringContainsString('tanggal_pinjam=2026-10-17', $html);
    }

    public function test_unit_perbaikan_ditandai_tidak_tersedia_dan_tidak_menerima_pesanan(): void
    {
        UnitBarang::where('kode_unit', 'PRJ-001')->update(['kondisi' => 'rusak', 'status' => 'perbaikan']);
        $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-20', '2026-10-21', [$this->barang->id => 1]);

        $data = $this->susun(false);
        $prj1 = collect($data['perUnit'])->first(fn ($b) => $b['unit']->kode_unit === 'PRJ-001');

        $this->assertTrue($prj1['tidakTersedia']);
        $this->assertSame([], $prj1['blok']);
        $this->assertSame('disetujui', $this->blok($data, 'PRJ-002')[0]['jenis']);
    }

    public function test_per_alat_ringkasan_filter_dan_form_ajuan_terisi(): void
    {
        $this->buatBarang('MKR', 2, 'Mikroskop');
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-15', '2026-10-16', [$this->barang->id => 2]);
        $this->serahkanUnit($p, ['PRJ-001', 'PRJ-002'], '2026-10-15 09:00');

        $data = $this->susun(false);
        $proyektor = collect($data['perAlat'])->first(fn ($b) => $b['barang']->kode === 'PRJ');
        $this->assertSame(['terpakai' => 2, 'kapasitas' => 3], $proyektor['hari'][1]); // 15 Okt
        $this->assertSame(['terpakai' => 0, 'kapasitas' => 3], $proyektor['hari'][5]); // 19 Okt

        $this->assertSame(2, $data['ringkasan']['diserahkan']);
        $this->assertSame(['Proyektor' => 2], $data['ringkasan']['populer']);

        // Filter cari hanya menyisakan unit Mikroskop
        $this->assertSame(2, $this->susun(true, ['q' => 'Mikroskop'])['jumlahUnit']);

        foreach (['tanggal', 'alat', 'minggu'] as $tab) {
            $this->get("/jadwal?tab={$tab}")->assertOk();
        }
        $this->get('/jadwal?tab=salah')->assertSessionHasErrors('tab');

        // Tautan dari jadwal mengisi tanggal di form ajuan; tanggal lampau diganti hari ini
        $this->get('/ajuan?barang_id='.$this->barang->id.'&tanggal_pinjam=2026-10-20')->assertSee('2026-10-20')->assertSee('2026-10-21');
        $this->get('/ajuan?tanggal_pinjam=2020-01-01')->assertSee('2026-10-15');
    }

    public function test_jadwal_admin_hanya_untuk_admin_dan_tampil_di_menu(): void
    {
        $this->get('/admin/jadwal')->assertRedirect('/login');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin/jadwal')->assertOk()->assertSee('Jadwal Pemakaian');
        $this->get('/admin/jadwal?tab=minggu')->assertOk()->assertSee('Tingkat pemakaian');
        $this->get('/')->assertSee('Jadwal Alat');
        $this->assertSame(0, PeminjamanUnit::count());
    }
}
