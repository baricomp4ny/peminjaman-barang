<?php

namespace Tests\Feature;

use App\Http\Controllers\Peminjam\StatusController;
use App\Models\Barang;
use App\Models\Pengaturan;
use App\Models\User;
use App\Notifications\AjuanDisetujui;
use App\Notifications\AjuanTerkirim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Bukti peminjaman PDF, pembatalan lewat tautan bertanda tangan, dan persetujuan syarat di form ajuan. */
class BuktiPembatalanSyaratTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $admin;

    private User $peminjam;

    private Barang $barang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-15 10:00'));
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
        $this->peminjam = User::factory()->create(['name' => 'Rina Kartika']);
        $this->barang = $this->buatBarang('PRJ', 3, 'Proyektor');
    }

    // ---------- 1. Bukti peminjaman ----------

    public function test_admin_bisa_cetak_bukti_pdf_dari_detail_transaksi(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-16', '2026-10-18', [$this->barang->id => 1]);

        $this->actingAs($this->admin)->get("/admin/peminjaman/{$p->id}")->assertSee('Cetak Bukti (PDF)');
        $respon = $this->get("/admin/peminjaman/{$p->id}/bukti")->assertOk();
        $this->assertSame('application/pdf', $respon->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $respon->getContent());
        $this->assertStringContainsString("bukti-{$p->kode_peminjaman}.pdf", $respon->headers->get('content-disposition'));
    }

    public function test_bukti_tidak_bisa_diakses_tanpa_login_admin(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-16', '2026-10-18', [$this->barang->id => 1]);

        $this->get("/admin/peminjaman/{$p->id}/bukti")->assertRedirect('/login');
    }

    public function test_email_persetujuan_melampirkan_bukti_dan_tautan_batal(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-16', '2026-10-18', [$this->barang->id => 1]);

        $this->actingAs($this->admin)->post("/admin/ajuan/{$p->id}/setujui")->assertSessionHas('success');

        Notification::assertSentTo($this->peminjam, AjuanDisetujui::class, function (AjuanDisetujui $n) use ($p) {
            $mail = $n->toMail($this->peminjam);

            return count($mail->rawAttachments) === 1
                && $mail->rawAttachments[0]['name'] === "bukti-{$p->kode_peminjaman}.pdf"
                && str_starts_with($mail->rawAttachments[0]['data'], '%PDF')
                && collect($mail->outroLines)->contains(fn ($l) => str_contains($l, "/status/{$p->kode_peminjaman}/batal?"));
        });
    }

    // ---------- 2. Pembatalan lewat tautan bertanda tangan ----------

    public function test_batal_dari_tautan_email_untuk_ajuan_menunggu_dan_disetujui(): void
    {
        foreach (['menunggu', 'disetujui'] as $status) {
            $p = $this->buatPeminjaman($this->peminjam, $status, '2026-10-16', '2026-10-18', [$this->barang->id => 1]);
            $url = StatusController::tautanBatal($p);

            $this->get($url)->assertOk()->assertSee('Ya, batalkan ajuan');
            $this->post($url)->assertRedirect("/status/{$p->kode_peminjaman}")->assertSessionHas('success');
            $this->assertSame('dibatalkan', $p->fresh()->status->value);
        }
    }

    public function test_tautan_batal_palsu_kedaluwarsa_atau_status_tidak_boleh_ditolak(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-16', '2026-10-18', [$this->barang->id => 1]);
        $url = StatusController::tautanBatal($p);

        // Tanda tangan diubah / tanpa tanda tangan
        $this->get(str_replace('signature=', 'signature=x', $url))->assertForbidden();
        $this->get("/status/{$p->kode_peminjaman}/batal")->assertForbidden();
        $this->post("/status/{$p->kode_peminjaman}/batal")->assertForbidden();

        // Lewat tanggal rencana kembali → tautan kedaluwarsa
        $this->travelTo(Carbon::parse('2026-10-19 08:00'));
        $this->get($url)->assertForbidden();
        $this->assertSame('menunggu', $p->fresh()->status->value);
    }

    public function test_ajuan_yang_sudah_diambil_tidak_bisa_dibatalkan_lewat_tautan(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-14', '2026-10-18', [$this->barang->id => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);
        $url = StatusController::tautanBatal($p->fresh());

        $this->get($url)->assertOk()->assertSee('tidak bisa dibatalkan lagi')->assertDontSee('Ya, batalkan ajuan');
        $this->post($url)->assertSessionHas('error');
        $this->assertSame('dipinjam', $p->fresh()->status->value);
    }

    // ---------- 3. Persetujuan syarat ----------

    private function dataAjuan(array $ubah = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso', 'nomor_identitas' => 'J0301209876', 'email' => 'budi@contoh.test', 'no_hp' => '081234567890',
            'tujuan' => 'Praktikum kultur jaringan', 'tanggal_pinjam' => '2026-10-16', 'tanggal_rencana_kembali' => '2026-10-17',
            'items' => [['barang_id' => $this->barang->id, 'jumlah' => 1]],
            'setuju_syarat' => '1',
            'surat_peminjaman' => UploadedFile::fake()->create('surat.pdf', 120, 'application/pdf'),
        ], $ubah);
    }

    public function test_form_ajuan_wajib_menyetujui_syarat_yang_diatur_admin(): void
    {
        Pengaturan::atur(Pengaturan::SYARAT_PEMINJAMAN, 'Alat wajib dikembalikan dalam keadaan bersih.');

        $this->get('/ajuan')->assertOk()
            ->assertSee('Alat wajib dikembalikan dalam keadaan bersih.')
            ->assertSee('Baca selengkapnya') // teks syarat dilipat
            ->assertSee('Saya menyetujui syarat peminjaman dan bertanggung jawab atas barang yang dipinjam');

        $this->post('/ajuan', $this->dataAjuan(['setuju_syarat' => null]))
            ->assertSessionHasErrors(['setuju_syarat' => 'Centang persetujuan syarat peminjaman untuk mengirim ajuan.']);

        $this->post('/ajuan', $this->dataAjuan())->assertSessionHasNoErrors();
        Notification::assertSentTo(User::where('nomor_identitas', 'J0301209876')->first(), AjuanTerkirim::class,
            fn ($n, $ch, $user) => collect($n->toMail($user)->outroLines)->contains(fn ($l) => str_contains($l, '/batal?')));
    }

    public function test_admin_mengubah_teks_syarat_dan_validasinya(): void
    {
        $this->actingAs($this->admin)->get('/admin/pengaturan/aturan')->assertOk()->assertSee('Syarat peminjaman');

        $this->put('/admin/pengaturan/aturan', [
            'lama_pinjam_maks' => 7, 'unit_maks_per_ajuan' => 3, 'lama_penangguhan_hari' => 7, 'batas_ambil_hari' => 1, 'estimasi_peninjauan_hari' => 2,
            'syarat_peminjaman' => '',
        ])->assertSessionHasErrors('syarat_peminjaman');

        $panjang = str_repeat('Syarat panjang. ', 150); // > 255 karakter: butuh kolom TEXT
        $this->put('/admin/pengaturan/aturan', [
            'lama_pinjam_maks' => 7, 'unit_maks_per_ajuan' => 3, 'lama_penangguhan_hari' => 7, 'batas_ambil_hari' => 1, 'estimasi_peninjauan_hari' => 2,
            'syarat_peminjaman' => $panjang,
        ])->assertSessionHasNoErrors();
        $this->assertSame(trim($panjang), Pengaturan::teks(Pengaturan::SYARAT_PEMINJAMAN));
    }
}
