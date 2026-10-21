<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Tampilan Laporan gaya kartu: kartu angka, filter satu baris, baris dengan panel detail + aksi. */
class LaporanTampilanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Notification::fake();
        $this->seed();
        $this->actingAs(User::admin()->first());
    }

    public function test_keempat_laporan_tampil_dengan_kartu_dan_baris_detail(): void
    {
        $kartu = [
            'inventaris' => ['Total unit', 'Kondisi rusak', 'Buka unit & perbaikan'],
            'pengembalian' => ['Unit dikembalikan', 'Rusak / hilang', 'Buka transaksi'],
            'keterlambatan' => ['Masih terlambat', 'Terlambat terlama (hari)', 'Buka transaksi'],
            'statistik' => ['Total transaksi', 'Tidak pernah dipinjam', 'Lihat jadwal alat'],
        ];

        foreach ($kartu as $jenis => $teks) {
            $this->get("/admin/laporan/{$jenis}")->assertOk()
                ->assertSeeInOrder([...array_slice($teks, 0, 2), 'Rincian ringkasan'])
                ->assertSee($teks[2])
                ->assertSee('Excel')
                // Pilihan jenis laporan menaut ke keempat laporan
                ->assertSee(route('admin.laporan.show', 'statistik'), false);
        }
    }

    public function test_keterlambatan_berjalan_punya_tombol_whatsapp(): void
    {
        // Seeder: Eko (no. HP terisi) masih terlambat mengembalikan pH Meter
        $this->get('/admin/laporan/keterlambatan')->assertOk()
            ->assertSee('https://wa.me/62', false)
            ->assertSee('2 (berjalan)');
    }

    public function test_inventaris_tanpa_filter_periode_dan_ekspor_tidak_berubah(): void
    {
        $this->get('/admin/laporan/inventaris')->assertOk()->assertDontSee('name="dari"', false);
        $this->get('/admin/laporan/pengembalian')->assertOk()->assertSee('name="dari"', false);

        $this->get('/admin/laporan/inventaris?format=excel')->assertOk()->assertDownload();
        $this->get('/admin/laporan/inventaris?format=pdf')->assertOk();
    }
}
