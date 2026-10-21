<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Tombol "Lihat Alat Ini dalam Matriks" di detail alat menyaring jadwal ke satu jenis alat. */
class JadwalFilterAlatTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    public function test_matriks_hanya_menampilkan_alat_yang_dipilih(): void
    {
        $prj = $this->buatBarang('PRJ', 2, 'Proyektor');
        $this->buatBarang('MKR', 2, 'Mikroskop');

        $this->get(route('peminjam.katalog.show', $prj))->assertOk()
            ->assertSee('Lihat Alat Ini dalam Matriks')
            ->assertSee(route('peminjam.jadwal', ['barang_id' => $prj->id, 'tab' => 'tanggal']));

        $this->get(route('peminjam.jadwal', ['barang_id' => $prj->id]))->assertOk()
            ->assertSee('PRJ-001')->assertDontSee('MKR-001')
            ->assertSee('Tampilkan semua alat');

        $this->get(route('peminjam.jadwal', ['barang_id' => 'bukan-ulid']))->assertSessionHasErrors('barang_id');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.jadwal', ['barang_id' => $prj->id]))->assertOk()
            ->assertSee('PRJ-001')->assertDontSee('MKR-001');
    }
}
