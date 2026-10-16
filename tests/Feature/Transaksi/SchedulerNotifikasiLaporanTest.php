<?php

namespace Tests\Feature\Transaksi;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\Pengaturan;
use App\Models\UnitBarang;
use App\Models\User;
use App\Notifications\AjuanBaru;
use App\Notifications\AjuanKedaluwarsa;
use App\Notifications\PengingatPengembalian;
use App\Services\LaporanService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

class SchedulerNotifikasiLaporanTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $peminjam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 10)->setTime(10, 0));
        $this->peminjam = User::factory()->create();
    }

    // ---------- Scheduler: kedaluwarsa ----------

    public function test_ajuan_disetujui_yang_lewat_batas_ambil_menjadi_kedaluwarsa(): void
    {
        Notification::fake();
        $b = $this->buatBarang('PRJ', 5);
        // batas ambil 1 hari: pinjam 8 Okt → paling lambat 9 Okt → 10 Okt kedaluwarsa
        $lewat = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-08', '2026-10-12', [$b->id => 1]);
        // pinjam 9 Okt → paling lambat 10 Okt (hari ini) → belum kedaluwarsa
        $masih = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-09', '2026-10-12', [$b->id => 1]);
        $menunggu = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-01', '2026-10-02', [$b->id => 1]);

        $this->artisan('peminjaman:kedaluwarsa')->expectsOutputToContain('1 ajuan')->assertSuccessful();

        $this->assertSame('kedaluwarsa', $lewat->fresh()->status->value);
        $this->assertSame('disetujui', $masih->fresh()->status->value);
        $this->assertSame('menunggu', $menunggu->fresh()->status->value);
        Notification::assertSentToTimes($this->peminjam, AjuanKedaluwarsa::class, 1);

        // Dijalankan lagi tidak memproses ulang
        $this->artisan('peminjaman:kedaluwarsa')->expectsOutputToContain('0 ajuan')->assertSuccessful();
    }

    public function test_opsi_tanggal_untuk_simulasi(): void
    {
        $b = $this->buatBarang('PRJ', 5);
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-15', '2026-10-16', [$b->id => 1]);

        $this->artisan('peminjaman:kedaluwarsa', ['--tanggal' => '2026-10-16'])->assertSuccessful();
        $this->assertSame('disetujui', $p->fresh()->status->value);

        $this->artisan('peminjaman:kedaluwarsa', ['--tanggal' => '2026-10-17'])->assertSuccessful();
        $this->assertSame('kedaluwarsa', $p->fresh()->status->value);
    }

    public function test_batas_ambil_mengikuti_pengaturan(): void
    {
        Pengaturan::atur(Pengaturan::BATAS_AMBIL_HARI, 3);
        $b = $this->buatBarang('PRJ', 5);
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-08', '2026-10-15', [$b->id => 1]);

        $this->artisan('peminjaman:kedaluwarsa')->assertSuccessful();

        $this->assertSame('disetujui', $p->fresh()->status->value);
    }

    // ---------- Scheduler: pengingat H-1 ----------

    public function test_pengingat_h1_dikirim_sekali_saja(): void
    {
        $b = $this->buatBarang('PRJ', 5);
        $besok = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-09', '2026-10-11', [$b->id => 1]);
        $this->serahkanUnit($besok, ['PRJ-001']);
        $lusa = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-09', '2026-10-12', [$b->id => 1]);
        $this->serahkanUnit($lusa, ['PRJ-002']);

        $this->artisan('peminjaman:pengingat')->expectsOutputToContain('1 pengingat')->assertSuccessful();
        $this->artisan('peminjaman:pengingat')->expectsOutputToContain('0 pengingat')->assertSuccessful();

        $this->assertSame(1, $this->peminjam->notifications()->where('type', PengingatPengembalian::class)->count());
        $this->assertSame($besok->id, $this->peminjam->notifications()->first()->data['peminjaman_id']);
    }

    public function test_jadwal_terdaftar(): void
    {
        $jadwal = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command.' @ '.$e->expression)->join("\n");

        $this->assertStringContainsString('peminjaman:kedaluwarsa', $jadwal);
        $this->assertStringContainsString('5 0 * * *', $jadwal);
        $this->assertStringContainsString('peminjaman:pengingat', $jadwal);
        $this->assertStringContainsString('0 7 * * *', $jadwal);
    }

    // ---------- Notifikasi email (peminjam tidak login) ----------

    public function test_email_peminjam_berisi_tautan_status_yang_bisa_dibuka_tanpa_login(): void
    {
        $b = $this->buatBarang('PRJ', 1);
        $p = $this->buatPeminjaman($this->peminjam, 'kedaluwarsa', '2026-10-01', '2026-10-02', [$b->id => 1]);

        $mail = (new AjuanKedaluwarsa($p))->toMail($this->peminjam);
        $this->assertStringContainsString($p->kode_peminjaman, $mail->subject);
        $this->assertStringContainsString('signature=', $mail->actionUrl);

        $this->get($mail->actionUrl)->assertOk()->assertSee($p->kode_peminjaman)->assertSee('kedaluwarsa');
        $this->assertGuest();
    }

    public function test_email_admin_menuju_halaman_detail_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $b = $this->buatBarang('PRJ', 1);
        $p = $this->buatPeminjaman($this->peminjam, 'menunggu', '2026-10-11', '2026-10-12', [$b->id => 1]);

        $mail = (new AjuanBaru($p))->toMail($admin);

        $this->assertSame(route('admin.peminjaman.show', $p), $mail->actionUrl);
    }
    // ---------- Laporan & dashboard ----------

    public function test_semua_laporan_tampil_dan_bisa_diunduh(): void
    {
        $admin = User::factory()->admin()->create();
        $b = $this->buatBarang('PRJ', 3);
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-02', '2026-10-05', [$b->id => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);

        foreach (['peminjaman', 'pengembalian', 'inventaris', 'keterlambatan', 'denda'] as $jenis) {
            $this->actingAs($admin)->get("/admin/laporan/{$jenis}?dari=2026-10-01&sampai=2026-10-10")->assertOk();

            $pdf = $this->get("/admin/laporan/{$jenis}?dari=2026-10-01&sampai=2026-10-10&format=pdf");
            $pdf->assertOk();
            $this->assertSame('application/pdf', $pdf->headers->get('content-type'));

            $xls = $this->get("/admin/laporan/{$jenis}?format=excel");
            $xls->assertOk();
            $this->assertStringContainsString('.xlsx', $xls->headers->get('content-disposition'));
        }

        // Pinjaman terlambat muncul di laporan keterlambatan
        $this->get('/admin/laporan/keterlambatan')->assertSee('PRJ-001')->assertSee('Belum kembali');
    }

    public function test_laporan_inventaris_berisi_keterangan_kondisi_dan_tahun_pembelian(): void
    {
        $b = $this->buatBarang('PRJ', 2);
        UnitBarang::where('kode_unit', 'PRJ-001')->update(['tanggal_perolehan' => '2023-04-01']);
        UnitBarang::where('kode_unit', 'PRJ-002')->update(['kondisi' => 'rusak', 'status' => 'perbaikan', 'tanggal_perolehan' => '2025-02-01']);
        $p = $this->buatPeminjaman($this->peminjam, 'selesai', '2026-10-01', '2026-10-02', [$b->id => 1]);
        PeminjamanUnit::create([
            'peminjaman_id' => $p->id, 'unit_barang_id' => UnitBarang::where('kode_unit', 'PRJ-002')->value('id'),
            'dikembalikan_pada' => '2026-10-02 10:00', 'kondisi_kembali' => 'rusak', 'catatan' => 'Lensa retak',
        ]);

        $laporan = app(LaporanService::class)->buat('inventaris', now(), now());

        $this->assertContains('Keterangan Kondisi', $laporan['kolom']);
        $this->assertContains('Tahun Pembelian', $laporan['kolom']);
        [$baik, $rusak] = $laporan['baris'];
        $this->assertSame(['Baik, siap dipinjam', '2023'], [$baik[6], $baik[8]]);
        $this->assertSame('Rusak, dalam perbaikan — Lensa retak (kembali 02/10/2026)', $rusak[6]);
        $this->assertSame('2025', $rusak[8]);
        $this->assertSame(1, $laporan['ringkasan']['Kondisi rusak']);
        $this->assertSame('1 unit (1 rusak)', $laporan['ringkasan']['Tahun 2025']);
    }

    public function test_validasi_filter_laporan(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/laporan/peminjaman?dari=2026-10-10&sampai=2026-10-01')->assertSessionHasErrors('sampai');
        $this->get('/admin/laporan/tidakada')->assertNotFound();
        $this->actingAs($this->peminjam)->get('/admin/laporan/peminjaman')->assertForbidden();
    }

    public function test_dashboard_dan_halaman_transaksi_terisi(): void
    {
        $this->seed();
        $admin = User::where('role', 'admin')->first();
        $andi = User::where('email', 'andi@peminjaman.test')->first();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Ajuan menunggu')->assertSee('Terlambat');
        $this->get('/admin/ajuan')->assertOk()->assertSee('Andi Pratama');
        foreach (['disetujui', 'dipinjam', 'terlambat'] as $tab) {
            $this->get("/admin/peminjaman?tab={$tab}")->assertOk();
        }
        $this->get('/admin/pengembalian')->assertOk();
        $this->get('/admin/riwayat?status=selesai')->assertOk();
        foreach (Peminjaman::all() as $p) {
            $this->get("/admin/peminjaman/{$p->id}")->assertOk();
        }

        // Halaman publik peminjam (tanpa login)
        auth()->logout();
        $this->get('/')->assertOk()->assertSee('Mikroskop Stereo')->assertSee('Katalog Alat')->assertSee('Alat Ukur &amp; Analisis', false);
        $this->get('/katalog/'.Barang::value('id'))->assertOk();
        $this->get('/ajuan')->assertOk();
        foreach ($andi->peminjaman as $p) {
            $this->post('/status', ['kode' => $p->kode_peminjaman, 'nomor_identitas' => $andi->nomor_identitas]);
            $this->get("/status/{$p->kode_peminjaman}")->assertOk();
        }
    }
}
