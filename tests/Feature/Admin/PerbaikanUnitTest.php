<?php

namespace Tests\Feature\Admin;

use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use App\Models\RiwayatKondisiUnit;
use App\Models\UnitBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Tombol "Perbaikan selesai" dan riwayat kondisi unit. */
class PerbaikanUnitTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    public function test_unit_rusak_diperbaiki_lalu_tersedia_dan_riwayat_tercatat(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Petugas Lab']);
        $this->buatBarang('PRJ', 2);
        $unit = UnitBarang::where('kode_unit', 'PRJ-001')->firstOrFail();

        // 1) Admin menandai rusak lewat form ubah unit (dengan keterangan)
        $this->actingAs($admin)->put("/admin/unit/{$unit->id}", [
            'lokasi_id' => $unit->lokasi_id, 'kondisi' => 'rusak', 'status' => 'perbaikan',
            'keterangan' => 'Lensa retak saat dibersihkan',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('riwayat_kondisi_unit', ['unit_barang_id' => $unit->id, 'jenis' => 'ubah_manual', 'keterangan' => 'Lensa retak saat dibersihkan']);

        // Simpan tanpa perubahan kondisi/status → tidak menambah riwayat
        $this->put("/admin/unit/{$unit->id}", ['lokasi_id' => $unit->lokasi_id, 'kondisi' => 'rusak', 'status' => 'perbaikan']);
        $this->assertSame(1, RiwayatKondisiUnit::count());

        // 2) Halaman unit menampilkan panel perbaikan + riwayat; daftar unit punya tautan cepat
        $this->get("/admin/unit/{$unit->id}/edit")->assertOk()
            ->assertSee('Unit sedang dalam perbaikan')->assertSee('Riwayat Kondisi')->assertSee('Lensa retak saat dibersihkan');
        $this->get('/admin/unit')->assertSee('Perbaikan selesai');

        // 3) Keterangan wajib
        $this->post("/admin/unit/{$unit->id}/perbaikan-selesai", ['keterangan' => ''])->assertSessionHasErrors('keterangan');

        $this->post("/admin/unit/{$unit->id}/perbaikan-selesai", ['keterangan' => 'Lensa diganti teknisi'])
            ->assertRedirect("/admin/unit/{$unit->id}/edit")->assertSessionHas('success');

        $unit->refresh();
        $this->assertSame(KondisiUnit::Baik, $unit->kondisi);
        $this->assertSame(StatusUnit::Tersedia, $unit->status);
        $this->assertDatabaseHas('riwayat_kondisi_unit', [
            'unit_barang_id' => $unit->id, 'jenis' => 'perbaikan_selesai', 'kondisi_lama' => 'rusak', 'kondisi_baru' => 'baik',
            'status_baru' => 'tersedia', 'dicatat_oleh' => $admin->id,
        ]);
        $this->get("/admin/unit/{$unit->id}/edit")->assertDontSee('Unit sedang dalam perbaikan')->assertSee('Petugas Lab');

        // 4) Unit yang tidak dalam perbaikan ditolak
        $this->post("/admin/unit/{$unit->id}/perbaikan-selesai", ['keterangan' => 'Coba lagi'])->assertSessionHas('error');
        $this->assertSame(2, RiwayatKondisiUnit::count());

        // 5) Hanya admin
        $this->actingAs(User::factory()->create())->post("/admin/unit/{$unit->id}/perbaikan-selesai", ['keterangan' => 'Lensa diganti'])->assertForbidden();
    }
}
