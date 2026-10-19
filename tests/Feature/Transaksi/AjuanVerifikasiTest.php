<?php

namespace Tests\Feature\Transaksi;

use App\Http\Controllers\Peminjam\StatusController;
use App\Models\Peminjaman;
use App\Models\Pengaturan;
use App\Models\User;
use App\Notifications\AjuanBaru;
use App\Notifications\AjuanDisetujui;
use App\Notifications\AjuanDitolak;
use App\Notifications\AjuanTerkirim;
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
        // Peminjam TIDAK login: identitas diisi di form publik
        return $this->post('/ajuan', array_merge([
            'name' => $this->peminjam->name,
            'nomor_identitas' => $this->peminjam->nomor_identitas,
            'email' => $this->peminjam->email,
            'no_hp' => '081234567890',
            'unit' => 'Teknik Informatika',
            'tujuan' => 'Presentasi seminar',
            'setuju_syarat' => '1',
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

        $p = null;
        $res = $this->ajukan()->assertSessionHasNoErrors()->assertSessionHas('success');

        $p = Peminjaman::firstOrFail();
        // Langsung diarahkan ke halaman status ajuannya (tanpa login)
        $res->assertRedirect("/status/{$p->kode_peminjaman}");
        $this->get("/status/{$p->kode_peminjaman}")->assertOk()->assertSee($p->kode_peminjaman)->assertSee('<svg', false);
        $this->assertSame($this->peminjam->id, $p->user_id);
        Notification::assertSentTo($this->peminjam, AjuanTerkirim::class);
        $this->assertSame('menunggu', $p->status->value);
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

        $this->ajukan()->assertSessionHasErrors('nomor_identitas');
        $this->assertSame(1, Peminjaman::count());
    }

    // ---------- Identitas peminjam (tanpa login) ----------

    public function test_nim_baru_membuat_data_peminjam_tanpa_akun_login(): void
    {
        $this->siapkanBarang();

        $this->ajukan(['name' => 'Rina Baru', 'nomor_identitas' => '2299001', 'email' => 'Rina@Example.com'])->assertSessionHasNoErrors();

        $rina = User::where('nomor_identitas', '2299001')->firstOrFail();
        $this->assertSame('peminjam', $rina->role->value);
        $this->assertSame('rina@example.com', $rina->email);
        $this->assertNull($rina->password);
        $this->assertSame(1, $rina->peminjaman()->count());
    }

    public function test_nim_terdaftar_dengan_email_lain_ditolak(): void
    {
        $this->siapkanBarang();

        $this->ajukan(['email' => 'penyusup@example.com'])->assertSessionHasErrors('email');
        $this->assertSame(0, Peminjaman::count());
        $this->assertNotSame('penyusup@example.com', $this->peminjam->fresh()->email);
    }

    public function test_email_yang_dipakai_nim_lain_ditolak(): void
    {
        $this->siapkanBarang();

        $this->ajukan(['nomor_identitas' => '2299002', 'email' => $this->peminjam->email])->assertSessionHasErrors('email');
        $this->assertSame(0, Peminjaman::count());
    }

    public function test_peminjam_nonaktif_dan_nomor_milik_admin_ditolak(): void
    {
        $this->siapkanBarang();
        $this->peminjam->forceFill(['status' => 'nonaktif'])->save();
        $this->ajukan()->assertSessionHasErrors('nomor_identitas');

        $this->admin->forceFill(['nomor_identitas' => 'ADM-01'])->save();
        $this->ajukan(['nomor_identitas' => 'ADM-01', 'email' => $this->admin->email])->assertSessionHasErrors('nomor_identitas');
        $this->assertSame(0, Peminjaman::count());
    }

    public function test_kontak_diperbarui_tapi_nama_tidak_ditimpa_dari_form(): void
    {
        $this->siapkanBarang();
        $namaAsli = $this->peminjam->name;

        $this->ajukan(['name' => 'Nama Lain', 'no_hp' => '089911112222', 'unit' => 'Manajemen'])->assertSessionHasNoErrors();

        $this->peminjam->refresh();
        $this->assertSame($namaAsli, $this->peminjam->name);
        $this->assertSame('089911112222', $this->peminjam->no_hp);
        $this->assertSame('Manajemen', $this->peminjam->unit);
    }

    public function test_validasi_identitas_dan_jebakan_bot(): void
    {
        $this->siapkanBarang();

        $this->ajukan(['name' => '', 'nomor_identitas' => '', 'email' => 'bukan-email', 'no_hp' => 'abc'])
            ->assertSessionHasErrors(['name', 'nomor_identitas', 'email', 'no_hp']);
        $this->ajukan(['nomor_identitas' => '22<script>'])->assertSessionHasErrors('nomor_identitas');
        $this->ajukan(['website' => 'http://spam.example'])->assertSessionHasErrors('website');
        $this->assertSame(0, Peminjaman::count());
    }
    // ---------- Verifikasi ----------

    public function test_admin_menyetujui_ajuan_dan_peminjam_mendapat_notifikasi(): void
    {
        $this->siapkanBarang();
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->actingAs($this->admin)->get("/admin/peminjaman/{$p->id}")->assertOk()->assertSee('Setujui');
        $this->post("/admin/ajuan/{$p->id}/setujui")->assertSessionHas('success');

        $p->refresh();
        $this->assertSame('disetujui', $p->status->value);
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

        $this->assertSame('disetujui', $a->fresh()->status->value);
        $this->assertSame('menunggu', $b->fresh()->status->value);
    }

    public function test_tolak_wajib_alasan(): void
    {
        $this->siapkanBarang();
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->actingAs($this->admin)->post("/admin/ajuan/{$p->id}/tolak", ['alasan_penolakan' => ''])
            ->assertSessionHasErrorsIn('tolak', 'alasan_penolakan');
        $this->post("/admin/ajuan/{$p->id}/tolak", ['alasan_penolakan' => 'pendek'])
            ->assertSessionHasErrorsIn('tolak', 'alasan_penolakan');
        $this->assertSame('menunggu', $p->fresh()->status->value);

        $this->post("/admin/ajuan/{$p->id}/tolak", ['alasan_penolakan' => 'Barang sedang dipakai untuk ujian.'])->assertSessionHas('success');
        $this->assertSame('ditolak', $p->fresh()->status->value);
        $this->assertSame('Barang sedang dipakai untuk ujian.', $p->fresh()->alasan_penolakan);
        Notification::assertSentTo($this->peminjam, AjuanDitolak::class);
    }

    public function test_tidak_bisa_memverifikasi_ajuan_yang_bukan_menunggu(): void
    {
        $this->siapkanBarang();
        $p = $this->buatPeminjaman($this->peminjam, 'dibatalkan', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->actingAs($this->admin)->post("/admin/ajuan/{$p->id}/setujui")->assertSessionHas('error');
        $this->post("/admin/ajuan/{$p->id}/tolak", ['alasan_penolakan' => 'Alasan cukup panjang'])->assertSessionHas('error');
        $this->assertSame('dibatalkan', $p->fresh()->status->value);
    }

    public function test_peminjam_tidak_bisa_menyetujui(): void
    {
        $this->siapkanBarang();
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->actingAs($this->peminjam)->post("/admin/ajuan/{$p->id}/setujui")->assertForbidden();
        $this->assertSame('menunggu', $p->fresh()->status->value);
    }

    // ---------- Cek status & pembatalan (tanpa login) ----------

    private function bukaStatus(Peminjaman $p, ?string $nim = null)
    {
        return $this->post('/status', ['kode' => strtolower($p->kode_peminjaman), 'nomor_identitas' => $nim ?? $this->peminjam->nomor_identitas]);
    }

    public function test_cek_status_dengan_kode_dan_nim(): void
    {
        $this->siapkanBarang();
        $p = $this->buatPeminjaman($this->peminjam, 'ditolak', '2026-10-11', '2026-10-12', [$this->barang->id => 1],
            ['alasan_penolakan' => 'Barang dipakai ujian praktik.']);

        // Tanpa akses → diarahkan ke form cek status
        $this->get("/status/{$p->kode_peminjaman}")->assertRedirect('/status');

        // NIM salah → pesan umum (tidak membocorkan kode mana yang benar)
        $this->bukaStatus($p, '0000000')->assertSessionHasErrors('kode');
        $this->post('/status', ['kode' => 'PJM-19990101-0001', 'nomor_identitas' => $this->peminjam->nomor_identitas])->assertSessionHasErrors('kode');

        $this->bukaStatus($p)->assertRedirect("/status/{$p->kode_peminjaman}");
        $this->get("/status/{$p->kode_peminjaman}")->assertOk()->assertSee('Barang dipakai ujian praktik.');
    }

    public function test_tautan_bertanda_tangan_dari_email_membuka_status(): void
    {
        $this->siapkanBarang();
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->get(StatusController::tautan($p))->assertOk()->assertSee($p->kode_peminjaman);

        // Tanda tangan diubah → tidak berlaku
        $this->flushSession();
        $this->get("/status/{$p->kode_peminjaman}?signature=palsu")->assertRedirect('/status');
    }

    public function test_peminjam_bisa_membatalkan_sebelum_barang_diambil(): void
    {
        $this->siapkanBarang();
        $menunggu = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);
        $disetujui = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->bukaStatus($menunggu);
        $this->post("/status/{$menunggu->kode_peminjaman}/batal")->assertSessionHas('success');
        $this->bukaStatus($disetujui);
        $this->post("/status/{$disetujui->kode_peminjaman}/batal")->assertSessionHas('success');

        $this->assertSame('dibatalkan', $menunggu->fresh()->status->value);
        $this->assertSame('dibatalkan', $disetujui->fresh()->status->value);
    }

    public function test_tidak_bisa_membatalkan_yang_sudah_dipinjam_atau_tanpa_akses(): void
    {
        $this->siapkanBarang();
        $dipinjam = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-12', [$this->barang->id => 1]);
        $this->serahkanUnit($dipinjam, ['PRJ-001']);
        $milikLain = $this->buatPeminjaman(User::factory()->create(), 'menunggu', '2026-10-11', '2026-10-12', [$this->barang->id => 1]);

        $this->bukaStatus($dipinjam);
        $this->post("/status/{$dipinjam->kode_peminjaman}/batal")->assertSessionHas('error');

        // Belum membuka status ajuan orang lain (tidak tahu NIM-nya) → ditolak
        $this->post("/status/{$milikLain->kode_peminjaman}/batal")->assertForbidden();
        $this->get("/status/{$milikLain->kode_peminjaman}")->assertRedirect('/status');

        $this->assertSame('dipinjam', $dipinjam->fresh()->status->value);
        $this->assertSame('menunggu', $milikLain->fresh()->status->value);
    }
}
