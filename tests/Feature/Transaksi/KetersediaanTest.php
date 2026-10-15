<?php

namespace Tests\Feature\Transaksi;

use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use App\Models\User;
use App\Services\KetersediaanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

class KetersediaanTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private KetersediaanService $svc;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 10)->setTime(10, 0));
        $this->svc = app(KetersediaanService::class);
        $this->user = User::factory()->create();
    }

    public function test_tanpa_transaksi_sisa_sama_dengan_unit_baik(): void
    {
        $b = $this->buatBarang('PRJ', 3);

        $this->assertSame(3, $this->svc->sisa($b->id, '2026-10-10', '2026-10-12'));
    }

    public function test_unit_rusak_perbaikan_hilang_tidak_dihitung(): void
    {
        $b = $this->buatBarang('PRJ', 4);
        UnitBarang::where('kode_unit', 'PRJ-001')->update(['kondisi' => 'rusak', 'status' => 'perbaikan']);
        UnitBarang::where('kode_unit', 'PRJ-002')->update(['kondisi' => 'hilang', 'status' => 'hilang']);

        $this->assertSame(2, $this->svc->sisa($b->id, '2026-10-10', '2026-10-10'));
    }

    /** Rumus tabrakan: mulai_A <= selesai_B dan selesai_A >= mulai_B (termasuk saling bersinggungan). */
    public function test_ajuan_disetujui_hanya_dihitung_bila_rentangnya_bertabrakan(): void
    {
        $b = $this->buatBarang('PRJ', 3);
        $this->buatPeminjaman($this->user, 'disetujui', '2026-10-12', '2026-10-14', [$b->id => 2]);

        $kasus = [
            ['2026-10-10', '2026-10-11', 3], // sebelum, tidak bertabrakan
            ['2026-10-10', '2026-10-12', 1], // bersinggungan di awal
            ['2026-10-13', '2026-10-13', 1], // di dalam
            ['2026-10-14', '2026-10-20', 1], // bersinggungan di akhir
            ['2026-10-11', '2026-10-15', 1], // membungkus
            ['2026-10-15', '2026-10-16', 3], // sesudah, tidak bertabrakan
        ];

        foreach ($kasus as [$mulai, $selesai, $harapan]) {
            $this->assertSame($harapan, $this->svc->sisa($b->id, $mulai, $selesai), "Rentang $mulai s/d $selesai");
        }
    }

    public function test_status_selain_disetujui_dan_dipinjam_tidak_mengurangi_stok(): void
    {
        $b = $this->buatBarang('PRJ', 2);
        foreach (['menunggu', 'ditolak', 'dibatalkan', 'kedaluwarsa', 'selesai'] as $status) {
            $this->buatPeminjaman($this->user, $status, '2026-10-10', '2026-10-12', [$b->id => 2]);
        }

        $this->assertSame(2, $this->svc->sisa($b->id, '2026-10-10', '2026-10-12'));
    }

    public function test_pinjaman_terlambat_tetap_memblokir_tanggal_ke_depan(): void
    {
        $b = $this->buatBarang('PRJ', 2);
        // Seharusnya kembali 8 Okt, sekarang 10 Okt dan belum kembali
        $p = $this->buatPeminjaman($this->user, 'disetujui', '2026-10-05', '2026-10-08', [$b->id => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);

        $this->assertSame(1, $this->svc->sisa($b->id, '2026-10-10', '2026-10-10'));
        $this->assertSame(1, $this->svc->sisa($b->id, '2026-10-09', '2026-10-09'), 'Hari ini sudah lewat rencana, unit tetap belum ada');

        // Rentang di masa depan (setelah hari ini) tidak terhalang oleh pinjaman yang rencananya sudah lewat
        $this->assertSame(2, $this->svc->sisa($b->id, '2026-10-20', '2026-10-21'));
    }

    public function test_pengembalian_sebagian_langsung_membebaskan_unit(): void
    {
        $b = $this->buatBarang('PRJ', 3);
        $p = $this->buatPeminjaman($this->user, 'disetujui', '2026-10-10', '2026-10-12', [$b->id => 2]);
        $this->serahkanUnit($p, ['PRJ-001', 'PRJ-002']);
        $this->assertSame(1, $this->svc->sisa($b->id, '2026-10-11', '2026-10-11'));

        PeminjamanUnit::where('peminjaman_id', $p->id)->first()->update(['dikembalikan_pada' => now(), 'kondisi_kembali' => 'baik']);
        UnitBarang::where('kode_unit', 'PRJ-001')->update(['status' => 'tersedia']);

        $this->assertSame(2, $this->svc->sisa($b->id, '2026-10-11', '2026-10-11'));
    }

    public function test_bisa_mengecualikan_ajuan_tertentu(): void
    {
        $b = $this->buatBarang('PRJ', 2);
        $p = $this->buatPeminjaman($this->user, 'disetujui', '2026-10-10', '2026-10-12', [$b->id => 2]);

        $this->assertSame(0, $this->svc->sisa($b->id, '2026-10-10', '2026-10-12'));
        $this->assertSame(2, $this->svc->sisa($b->id, '2026-10-10', '2026-10-12', $p->id));
    }

    public function test_kekurangan_mengembalikan_barang_yang_kurang_saja(): void
    {
        $a = $this->buatBarang('PRJ', 1);
        $b = $this->buatBarang('LPT', 5);

        $kurang = $this->svc->kekurangan([$a->id => 2, $b->id => 2], '2026-10-10', '2026-10-11');

        $this->assertSame([$a->id => 1], $kurang);
    }

    public function test_endpoint_json_ketersediaan_untuk_form_ajuan(): void
    {
        $b = $this->buatBarang('PRJ', 3);
        $this->buatPeminjaman($this->user, Peminjaman::STATUS_DISETUJUI, '2026-10-11', '2026-10-12', [$b->id => 1]);

        $this->actingAs($this->user)
            ->getJson('/peminjam/ketersediaan?mulai=2026-10-11&selesai=2026-10-11&barang[]='.$b->id)
            ->assertOk()
            ->assertExactJson([(string) $b->id => 2]);
    }
}
