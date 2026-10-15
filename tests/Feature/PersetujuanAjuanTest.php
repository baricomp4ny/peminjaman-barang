<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\UnitBarang;
use App\Models\User;
use App\Notifications\AjuanDisetujui;
use App\Notifications\AjuanDitolak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Sprint 5 — pengujian persetujuan ajuan (melengkapi Transaksi\AjuanVerifikasiTest). */
class PersetujuanAjuanTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $admin;

    private User $peminjam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-10 10:00'));
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
        $this->peminjam = User::factory()->create();
    }

    private function setujui(Peminjaman $p)
    {
        return $this->actingAs($this->admin)->post("/admin/ajuan/{$p->id}/setujui");
    }

    public function test_persetujuan_bersifat_atomik_bila_satu_barang_kurang(): void
    {
        $prj = $this->buatBarang('PRJ', 3);
        $lpt = $this->buatBarang('LPT', 1);
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$prj->id => 1, $lpt->id => 2]);

        $this->setujui($p)->assertSessionHas('error', fn ($e) => str_contains($e, 'diminta 2, sisa 1'));

        $p->refresh();
        $this->assertSame('menunggu', $p->status->value);
        $this->assertNull($p->diverifikasi_oleh);
        $this->assertNull($p->diverifikasi_pada);
        Notification::assertNothingSent();
    }

    public function test_unit_yang_rusak_setelah_ajuan_dibuat_ikut_diperhitungkan(): void
    {
        $prj = $this->buatBarang('PRJ', 2);
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$prj->id => 2]);

        UnitBarang::where('kode_unit', 'PRJ-002')->update(['kondisi' => 'rusak', 'status' => 'perbaikan']);

        $this->setujui($p)->assertSessionHas('error');
        $this->assertSame('menunggu', $p->fresh()->status->value);
    }

    public function test_pinjaman_terlambat_memblokir_persetujuan(): void
    {
        $prj = $this->buatBarang('PRJ', 1);
        $lama = $this->buatPeminjaman(User::factory()->create(), 'disetujui', '2026-10-05', '2026-10-08', [$prj->id => 1]);
        $this->serahkanUnit($lama, ['PRJ-001']);
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-11', [$prj->id => 1]);

        // Rencana kembali 8 Okt sudah lewat, tapi unitnya belum kembali → tidak boleh dijanjikan ke orang lain
        $this->setujui($p)->assertSessionHas('error');

        // Tambah satu unit → persetujuan bisa dilakukan
        UnitBarang::create(['barang_id' => $prj->id, 'lokasi_id' => UnitBarang::value('lokasi_id'), 'kode_unit' => 'PRJ-002']);
        $this->setujui($p)->assertSessionHas('success');
    }

    public function test_persetujuan_ganda_ditolak(): void
    {
        $prj = $this->buatBarang('PRJ', 3);
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$prj->id => 1]);

        $this->setujui($p)->assertSessionHas('success');
        $diverifikasiPertama = $p->fresh()->diverifikasi_pada;

        $this->travel(5)->minutes();
        $this->setujui($p)->assertSessionHas('error', fn ($e) => str_contains($e, 'status ajuan sekarang "disetujui"'));

        $this->assertEquals($diverifikasiPertama, $p->fresh()->diverifikasi_pada);
        Notification::assertSentToTimes($this->peminjam, AjuanDisetujui::class, 1);
    }

    public function test_ajuan_yang_disetujui_mengurangi_stok_untuk_ajuan_berikutnya(): void
    {
        $prj = $this->buatBarang('PRJ', 2);
        $a = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-13', [$prj->id => 2]);
        $bentrok = $this->buatPeminjaman(User::factory()->create(), 'menunggu', '2026-10-13', '2026-10-14', [$prj->id => 1]);
        $aman = $this->buatPeminjaman(User::factory()->create(), 'menunggu', '2026-10-14', '2026-10-15', [$prj->id => 2]);

        $this->setujui($a)->assertSessionHas('success');
        $this->setujui($bentrok)->assertSessionHas('error');
        $this->setujui($aman)->assertSessionHas('success');
    }

    public function test_pembatalan_mengembalikan_stok(): void
    {
        $prj = $this->buatBarang('PRJ', 1);
        $a = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$prj->id => 1]);
        $b = $this->buatPeminjaman(User::factory()->create(), 'menunggu', '2026-10-11', '2026-10-12', [$prj->id => 1]);

        $this->setujui($a)->assertSessionHas('success');
        $this->setujui($b)->assertSessionHas('error');

        // Peminjam (tanpa login) membuka status dengan kode + NIM, lalu membatalkan
        $this->post('/status', ['kode' => $a->kode_peminjaman, 'nomor_identitas' => $this->peminjam->nomor_identitas]);
        $this->post("/status/{$a->kode_peminjaman}/batal")->assertSessionHas('success');
        $this->setujui($b)->assertSessionHas('success');
    }

    public function test_admin_nonaktif_tidak_bisa_menyetujui(): void
    {
        $prj = $this->buatBarang('PRJ', 1);
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$prj->id => 1]);
        $adminNonaktif = User::factory()->admin()->nonaktif()->create();

        $this->actingAs($adminNonaktif)->post("/admin/ajuan/{$p->id}/setujui")->assertRedirect('/login');
        $this->assertSame('menunggu', $p->fresh()->status->value);
    }

    public function test_alasan_penolakan_berisi_spasi_saja_ditolak_dan_disimpan_rapi(): void
    {
        $prj = $this->buatBarang('PRJ', 1);
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$prj->id => 1]);

        $this->actingAs($this->admin)->post("/admin/ajuan/{$p->id}/tolak", ['alasan_penolakan' => '              '])
            ->assertSessionHasErrorsIn('tolak', 'alasan_penolakan');
        $this->post("/admin/ajuan/{$p->id}/tolak", ['alasan_penolakan' => '   Stok dipakai untuk ujian.   '])
            ->assertSessionHas('success');

        $this->assertSame('Stok dipakai untuk ujian.', $p->fresh()->alasan_penolakan);
        Notification::assertSentTo($this->peminjam, AjuanDitolak::class,
            fn ($n) => str_contains($n->toArray($this->peminjam)['pesan'], 'Stok dipakai untuk ujian.'));
    }

    public function test_ajuan_yang_ditolak_tidak_mengurangi_stok(): void
    {
        $prj = $this->buatBarang('PRJ', 1);
        $a = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$prj->id => 1]);
        $b = $this->buatPeminjaman(User::factory()->create(), 'menunggu', '2026-10-11', '2026-10-12', [$prj->id => 1]);

        $this->actingAs($this->admin)->post("/admin/ajuan/{$a->id}/tolak", ['alasan_penolakan' => 'Tujuan tidak jelas sama sekali.']);
        $this->setujui($b)->assertSessionHas('success');
    }
}
