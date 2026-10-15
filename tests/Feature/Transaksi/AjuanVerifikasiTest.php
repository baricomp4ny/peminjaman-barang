<?php

namespace Tests\Feature\Transaksi;

use App\Models\Peminjaman;
use App\Models\Pengaturan;
use App\Models\User;
use App\Notifications\AjuanBaru;
use App\Notifications\AjuanDisetujui;
use App\Notifications\AjuanDitolak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

class AjuanVerifikasiTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $admin;
    private User $peminjam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 10)->setTime(10, 0));
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
        $this->peminjam = User::factory()->create();
    }

    private function ajukan(array $ubah = [])
    {
        return $this->actingAs($this->peminjam)->post('/peminjam/ajuan', array_merge([
            'tujuan' => 'Presentasi seminar',
            'tanggal_pinjam' => '2026-10-11',
            'tanggal_rencana_kembali' => '2026-10-13',
            'items' => [['barang_id' => $this->barang->id, 'jumlah' => 1]],
        ], $ubah));
    }

    private $barang;

    private function siapkanBarang(int $unit = 3): void
    {
        $this->barang = $this->buatBarang('PRJ', $unit, 'Proyektor');
    }

    // ---------- Ajuan ----------

    public function test_peminjam_bisa_mengajukan_dan_admin_mendapat_notifikasi(): void
    {
        $this->siapkanBarang();

        $this->ajukan()->assertSessionHasNoErrors()->assertSessionHas('success');

        $p = Peminjaman::firstOrFail();
        $this->assertSame('menunggu', $p->status);
        $this->assertMatchesRegularExpression('/^PJM-20261010-\d{4}$/', $p->kode_peminjaman);
        $this->assertSame(1, $p->detail()->first()->jumlah);
        Notification::assertSentTo($this->admin, AjuanBaru::class);
    }

    public function test_kode_peminjaman_berurutan_per_hari(): void
    {
        $this->siapkanBarang();

        $this->ajukan();
        $this->ajukan();

        $this->assertSame(['PJM-20261010-0001', 'PJM-20261010-0002'], Peminjaman::orderBy('id')->pluck('kode_peminjaman')->all());
    }

    public function test_validasi_tanggal_ajuan(): void
    {
        $this->siapkanBarang();

        $this->ajukan(['tanggal_pinjam' => '2026-10-09'])->assertSessionHasErrors('tanggal_pinjam');
        $this->ajukan(['tanggal_rencana_kembali' => '2026-10-10'])->assertSessionHasErrors('tanggal_rencana_kembali');
        // 11 → 19 = 8 hari > maks 7
        $this->ajukan(['tanggal_rencana_kembali' => '2026-10-19'])->assertSessionHasErrors('tanggal_rencana_kembali');
        $this->assertSame(0, Peminjaman::count());

        // 11 → 18 = tepat 7 hari, boleh; pinjam & kembali di hari yang sama juga boleh
        $this->ajukan(['tanggal_rencana_kembali' => '2026-10-18'])->assertSessionHasNoErrors();
        $this->ajukan(['tanggal_pinjam' => '2026-10-10', 'tanggal_rencana_kembali' => '2026-10-10'])->assertSessionHasNoErrors();
    }

    public function test_lama_pinjam_mengikuti_pengaturan(): void
    {
        $this->siapkanBarang();
        Pengaturan::atur(Pengaturan::LAMA_PINJAM_MAKS, 2);

        $this->ajukan(['tanggal_rencana_kembali' => '2026-10-14'])->assertSessionHasErrors('tanggal_rencana_kembali');
    }

    public function test_validasi_barang_ajuan(): void
    {
        $this->siapkanBarang(5);
        $lain = $this->buatBarang('LPT', 5);

        // total 4 > maks 3
        $this->ajukan(['items' => [['barang_id' => $this->barang->id, 'jumlah' => 2], ['barang_id' => $lain->id, 'jumlah' => 2]]])
            ->assertSessionHasErrors('items');
        // barang sama dua kali
        $this->ajukan(['items' => [['barang_id' => $this->barang->id, 'jumlah' => 1], ['barang_id' => $this->barang->id, 'jumlah' => 1]]])
            ->assertSessionHasErrors('items.1.barang_id');
        $this->ajukan(['items' => [['barang_id' => $this->barang->id, 'jumlah' => 0]]])->assertSessionHasErrors('items.0.jumlah');
        $this->ajukan(['items' => [['barang_id' => 999, 'jumlah' => 1]]])->assertSessionHasErrors('items.0.barang_id');
        $this->ajukan(['items' => []])->assertSessionHasErrors('items');
        $this->ajukan(['tujuan' => ''])->assertSessionHasErrors('tujuan');

        $this->assertSame(0, Peminjaman::count());
    }

    public function test_ajuan_ditolak_bila_stok_pada_rentang_tanggal_tidak_cukup(): void
    {
        $this->siapkanBarang(2);
        $lain = User::factory()->create();
        $this->buatPeminjaman($lain, 'disetujui', '2026-10-12', '2026-10-15', [$this->barang->id => 2]);

        $this->ajukan(['items' => [['barang_id' => $this->barang->id, 'jumlah' => 1]]])
            ->assertSessionHasErrors('items.0.jumlah');

        // Rentang yang tidak bertabrakan tetap bisa
        $this->ajukan(['tanggal_pinjam' => '2026-10-16', 'tanggal_rencana_kembali' => '2026-10-17'])->assertSessionHasNoErrors();
    }

    public function test_peminjam_yang_punya_pinjaman_terlambat_tidak_bisa_mengajukan(): void
    {
        $this->siapkanBarang();
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-01', '2026-10-05', [$this->barang->id => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);

        $this->ajukan()->assertSessionHasErrors('items');
        $this->assertSame(1, Peminjaman::count());
    }

    public function test_admin_tidak_bisa_mengajukan_lewat_halaman_peminjam(): void
    {
        $this->actingAs($this->admin)->get('/peminjam/ajuan/create')->assertForbidden();
    }

    // ---------- Verifikasi ----------

    public function test_admin_menyetujui_ajuan_dan_peminjam_mendapat_notifikasi(): void
    {
        $this->siapkanBarang();
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->actingAs($this->admin)->get("/admin/peminjaman/{$p->id}")->assertOk()->assertSee('Setujui');
        $this->post("/admin/ajuan/{$p->id}/setujui")->assertSessionHas('success');

        $p->refresh();
        $this->assertSame('disetujui', $p->status);
        $this->assertSame($this->admin->id, $p->diverifikasi_oleh);
        $this->assertNotNull($p->diverifikasi_pada);
        Notification::assertSentTo($this->peminjam, AjuanDisetujui::class);
    }

    public function test_persetujuan_mengecek_ulang_stok_sehingga_tidak_bisa_overbooking(): void
    {
        $this->siapkanBarang(3);
        $a = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-13', [$this->barang->id => 2]);
        $b = $this->buatPeminjaman(User::factory()->create(), 'menunggu', '2026-10-12', '2026-10-14', [$this->barang->id => 2]);

        $this->actingAs($this->admin)->post("/admin/ajuan/{$a->id}/setujui")->assertSessionHas('success');
        $this->post("/admin/ajuan/{$b->id}/setujui")->assertSessionHas('error');

        $this->assertSame('disetujui', $a->fresh()->status);
        $this->assertSame('menunggu', $b->fresh()->status);
    }

    public function test_tolak_wajib_alasan(): void
    {
        $this->siapkanBarang();
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->actingAs($this->admin)->post("/admin/ajuan/{$p->id}/tolak", ['alasan_penolakan' => ''])
            ->assertSessionHasErrorsIn('tolak', 'alasan_penolakan');
        $this->post("/admin/ajuan/{$p->id}/tolak", ['alasan_penolakan' => 'pendek'])
            ->assertSessionHasErrorsIn('tolak', 'alasan_penolakan');
        $this->assertSame('menunggu', $p->fresh()->status);

        $this->post("/admin/ajuan/{$p->id}/tolak", ['alasan_penolakan' => 'Barang sedang dipakai untuk ujian.'])->assertSessionHas('success');
        $this->assertSame('ditolak', $p->fresh()->status);
        $this->assertSame('Barang sedang dipakai untuk ujian.', $p->fresh()->alasan_penolakan);
        Notification::assertSentTo($this->peminjam, AjuanDitolak::class);
    }

    public function test_tidak_bisa_memverifikasi_ajuan_yang_bukan_menunggu(): void
    {
        $this->siapkanBarang();
        $p = $this->buatPeminjaman($this->peminjam, 'dibatalkan', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->actingAs($this->admin)->post("/admin/ajuan/{$p->id}/setujui")->assertSessionHas('error');
        $this->post("/admin/ajuan/{$p->id}/tolak", ['alasan_penolakan' => 'Alasan cukup panjang'])->assertSessionHas('error');
        $this->assertSame('dibatalkan', $p->fresh()->status);
    }

    public function test_peminjam_tidak_bisa_menyetujui(): void
    {
        $this->siapkanBarang();
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->actingAs($this->peminjam)->post("/admin/ajuan/{$p->id}/setujui")->assertForbidden();
        $this->assertSame('menunggu', $p->fresh()->status);
    }

    // ---------- Pembatalan ----------

    public function test_peminjam_bisa_membatalkan_sebelum_barang_diambil(): void
    {
        $this->siapkanBarang();
        $menunggu = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);
        $disetujui = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->actingAs($this->peminjam)->post("/peminjam/pinjaman/{$menunggu->id}/batal")->assertSessionHas('success');
        $this->post("/peminjam/pinjaman/{$disetujui->id}/batal")->assertSessionHas('success');

        $this->assertSame('dibatalkan', $menunggu->fresh()->status);
        $this->assertSame('dibatalkan', $disetujui->fresh()->status);
    }

    public function test_tidak_bisa_membatalkan_yang_sudah_dipinjam_atau_milik_orang_lain(): void
    {
        $this->siapkanBarang();
        $dipinjam = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-12', [$this->barang->id => 1]);
        $this->serahkanUnit($dipinjam, ['PRJ-001']);
        $milikLain = $this->buatPeminjaman(User::factory()->create(), 'menunggu', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->actingAs($this->peminjam)->post("/peminjam/pinjaman/{$dipinjam->id}/batal")->assertSessionHas('error');
        $this->post("/peminjam/pinjaman/{$milikLain->id}/batal")->assertForbidden();
        $this->get("/peminjam/pinjaman/{$milikLain->id}")->assertForbidden();

        $this->assertSame('dipinjam', $dipinjam->fresh()->status);
        $this->assertSame('menunggu', $milikLain->fresh()->status);
    }
}
