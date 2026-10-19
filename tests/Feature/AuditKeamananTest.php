<?php

namespace Tests\Feature;

use App\Exports\LaporanExport;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as JenisExcel;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/** Regresi hasil audit keamanan: upload template & ekspor Excel. */
class AuditKeamananTest extends TestCase
{
    use RefreshDatabase;

    private function dataAturan(UploadedFile $template): array
    {
        return [
            'lama_pinjam_maks' => 7, 'unit_maks_per_ajuan' => 3, 'lama_penangguhan_hari' => 7, 'batas_ambil_hari' => 1,
            'estimasi_peninjauan_hari' => 2, 'syarat_peminjaman' => 'Gunakan alat dengan hati-hati.',
            'template_surat' => $template,
        ];
    }

    public function test_template_tidak_pernah_tersimpan_sebagai_file_php(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        // Lapis 1: nama .php (walau isi PDF) ditolak validasi
        $this->put('/admin/pengaturan/aturan', $this->dataAturan(UploadedFile::fake()->create('shell.php', 10, 'application/pdf')))
            ->assertSessionHasErrors('template_surat');

        // Lapis 2: ekstensi tersimpan ditentukan dari isi file, bukan nama kiriman klien
        $this->put('/admin/pengaturan/aturan', $this->dataAturan(UploadedFile::fake()->create('template.DOCX.pdf', 10, 'application/pdf')))
            ->assertSessionHasNoErrors();

        $this->assertSame('template/template-surat-peminjaman.pdf', Pengaturan::teks(Pengaturan::TEMPLATE_SURAT));
        $this->assertSame([], array_values(array_filter(Storage::disk('public')->allFiles(), fn ($f) => ! str_ends_with($f, '.pdf'))));
    }

    public function test_ekspor_excel_tidak_menjalankan_formula_dari_input_publik(): void
    {
        $laporan = [
            'jenis' => 'peminjaman', 'judul' => 'Uji', 'periode' => 'Uji',
            'kolom' => ['Nama', 'Tujuan', 'Jumlah'],
            'baris' => [['=HYPERLINK("http://jahat.test","klik")', '+1+1', 5], ['@SUM(1,1)', '-', -3]],
            'ringkasan' => [],
        ];

        $berkas = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($berkas, Excel::raw(new LaporanExport($laporan), JenisExcel::XLSX));
        $sheet = IOFactory::load($berkas)->getActiveSheet();
        unlink($berkas);

        // Kop 3 baris + baris pemisah + header di baris 5 (tebal), data mulai baris 6
        $this->assertSame('Nama', $sheet->getCell('A5')->getValue());
        $this->assertTrue($sheet->getStyle('A5')->getFont()->getBold());
        foreach (['A6', 'B6', 'A7', 'B7'] as $sel) {
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell($sel)->getDataType(), "Sel {$sel} harus teks");
        }
        $this->assertSame('=HYPERLINK("http://jahat.test","klik")', $sheet->getCell('A6')->getValue());
        $this->assertSame(5, $sheet->getCell('C6')->getValue());
        $this->assertSame(-3, $sheet->getCell('C7')->getValue());
    }
}
