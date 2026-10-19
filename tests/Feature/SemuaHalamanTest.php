<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Peminjaman;
use App\Models\UnitBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Uji asap: setiap halaman GET (publik & admin) dibuka dengan data seeder, tidak boleh error 5xx. */
class SemuaHalamanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Notification::fake();
        $this->seed();
    }

    /** Nilai parameter route dari data seeder. */
    private function parameter(): array
    {
        return [
            'barang' => Barang::value('id'),
            'kategori' => Kategori::value('id'),
            'lokasi' => Lokasi::value('id'),
            'unit' => UnitBarang::value('id'),
            'peminjam' => User::peminjam()->value('id'),
            'akunAdmin' => User::admin()->value('id'),
            'jenis' => 'inventaris',
            'token' => 'token-uji',
        ];
    }

    public function test_semua_halaman_get_tidak_error(): void
    {
        $admin = User::admin()->first();
        $param = $this->parameter();
        $dilewati = ['login', 'password.request', 'password.reset', 'peminjam.status.batal.konfirmasi'];
        $dibuka = 0;

        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            /** @var Route $route */
            $nama = $route->getName();
            if (! in_array('GET', $route->methods(), true) || ! $nama || in_array($nama, $dilewati, true) || str_starts_with($route->uri(), '_') || str_contains($route->uri(), 'sanctum')) {
                continue;
            }

            $butuh = $route->parameterNames();
            $nilai = array_intersect_key($param, array_flip($butuh));
            if (count($nilai) !== count($butuh)) {
                continue;
            }

            $url = route($nama, $nilai);
            $respon = str_starts_with($route->uri(), 'admin') || in_array($nama, ['profile.edit', 'dashboard'], true)
                ? $this->actingAs($admin)->get($url)
                : $this->get($url);

            $this->assertLessThan(500, $respon->getStatusCode(), "Halaman {$nama} ({$url}) error {$respon->getStatusCode()}");
            $dibuka++;
        }

        // Halaman detail transaksi untuk SEMUA status + status publik + bukti PDF
        foreach (Peminjaman::all() as $p) {
            $this->actingAs($admin)->get(route('admin.peminjaman.show', $p))->assertOk();
            $this->get(route('admin.peminjaman.bukti', $p))->assertOk();
            $this->get(route('admin.peminjaman.serah', $p))->assertStatus($p->status->value === 'disetujui' ? 200 : 302);
            $this->get(route('admin.pengembalian.create', $p))->assertStatus($p->status->value === 'dipinjam' ? 200 : 302);
        }
        foreach (['peminjaman', 'pengembalian', 'inventaris', 'keterlambatan', 'statistik'] as $jenis) {
            $this->get(route('admin.laporan.show', ['jenis' => $jenis, 'format' => 'pdf']))->assertOk();
            $this->get(route('admin.laporan.show', ['jenis' => $jenis, 'format' => 'excel']))->assertOk();
        }

        $this->assertGreaterThan(30, $dibuka);
    }
}
