<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Template surat, upload surat wajib di form ajuan, dan akses surat khusus admin. */
class SuratPeminjamanTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private Barang $barang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-15 10:00'));
        Notification::fake();
        Storage::fake('public');
        $this->barang = $this->buatBarang('PRJ', 3, 'Proyektor');
    }

    private function dataAjuan(array $ubah = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso', 'nomor_identitas' => 'J0301209876', 'email' => 'budi@contoh.test', 'no_hp' => '081234567890',
            'tujuan' => 'Praktikum kultur jaringan', 'tanggal_pinjam' => '2026-10-16', 'tanggal_rencana_kembali' => '2026-10-17',
            'items' => [['barang_id' => $this->barang->id, 'jumlah' => 1]],
            'setuju_syarat' => '1',
            'surat_peminjaman' => UploadedFile::fake()->create('surat-budi.pdf', 300, 'application/pdf'),
        ], $ubah);
    }

    public function test_form_ajuan_menampilkan_info_template_estimasi_dan_upload(): void
    {
        Pengaturan::atur(Pengaturan::ESTIMASI_PENINJAUAN_HARI, 3);

        $this->get('/ajuan')->assertOk()
            ->assertSee('Surat peminjaman wajib disiapkan sebelum dikirim.')
            ->assertSee('Buka Template Surat')
            ->assertSee('Estimasi peninjauan maksimal 3 hari kerja.')
            ->assertSee('PDF, DOC, atau DOCX · maksimal 4 MB')
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_template_bawaan_bisa_diunduh_publik(): void
    {
        $respon = $this->get('/ajuan/template-surat')->assertOk();

        $this->assertStringContainsString('Template Surat Permohonan Peminjaman Alat.docx', $respon->headers->get('content-disposition'));
    }

    public function test_surat_wajib_dan_format_ukuran_divalidasi(): void
    {
        $this->post('/ajuan', $this->dataAjuan(['surat_peminjaman' => null]))
            ->assertSessionHasErrors(['surat_peminjaman' => 'Unggah surat peminjaman (PDF, DOC, atau DOCX).']);
        $this->post('/ajuan', $this->dataAjuan(['surat_peminjaman' => UploadedFile::fake()->image('foto.jpg')]))
            ->assertSessionHasErrors(['surat_peminjaman' => 'Surat peminjaman harus berformat PDF, DOC, atau DOCX.']);
        $this->post('/ajuan', $this->dataAjuan(['surat_peminjaman' => UploadedFile::fake()->create('besar.pdf', 5000, 'application/pdf')]))
            ->assertSessionHasErrors(['surat_peminjaman' => 'Ukuran surat peminjaman maksimal 4 MB.']);

        $this->assertSame(0, Peminjaman::count());
    }

    public function test_surat_disimpan_privat_dan_hanya_admin_bisa_membuka(): void
    {
        $this->post('/ajuan', $this->dataAjuan())->assertSessionHasNoErrors();

        $p = Peminjaman::firstOrFail();
        $this->assertStringStartsWith('surat/', $p->surat_peminjaman);
        Storage::disk('local')->assertExists($p->surat_peminjaman);
        Storage::disk('public')->assertMissing($p->surat_peminjaman);

        // Publik / belum login → diarahkan ke login
        $this->get("/admin/peminjaman/{$p->id}/surat")->assertRedirect('/login');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get("/admin/peminjaman/{$p->id}")->assertSee('Lihat Surat Peminjaman')->assertSee('Surat peminjaman terlampir');
        $this->get("/admin/peminjaman/{$p->id}/surat")->assertOk();
    }

    public function test_surat_dihapus_bila_ajuan_gagal_diproses(): void
    {
        // Peminjam nonaktif lolos validasi format tapi ditolak service → file tidak boleh tertinggal
        User::factory()->create(['nomor_identitas' => 'J0301209876', 'email' => 'budi@contoh.test', 'status' => 'nonaktif']);

        $this->post('/ajuan', $this->dataAjuan());

        $this->assertSame(0, Peminjaman::count());
        $this->assertSame([], Storage::disk('local')->allFiles('surat'));
    }

    public function test_admin_mengganti_template_dan_estimasi(): void
    {
        $admin = User::factory()->admin()->create();
        $data = [
            'lama_pinjam_maks' => 7, 'unit_maks_per_ajuan' => 3, 'lama_penangguhan_hari' => 7, 'batas_ambil_hari' => 1,
            'estimasi_peninjauan_hari' => 5, 'syarat_peminjaman' => 'Gunakan alat dengan hati-hati.',
        ];

        $this->actingAs($admin)->put('/admin/pengaturan/aturan', $data + ['template_surat' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertSessionHasErrors('template_surat');

        $this->put('/admin/pengaturan/aturan', $data + [
            'template_surat' => UploadedFile::fake()->create('template-baru.pdf', 50, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, Pengaturan::angka(Pengaturan::ESTIMASI_PENINJAUAN_HARI));
        $this->assertSame('template/template-surat-peminjaman.pdf', Pengaturan::teks(Pengaturan::TEMPLATE_SURAT));
        Storage::disk('public')->assertExists('template/template-surat-peminjaman.pdf');
        $this->assertStringContainsString('.pdf', $this->get('/ajuan/template-surat')->assertOk()->headers->get('content-disposition'));
    }
}
