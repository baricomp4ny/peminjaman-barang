<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Satu kelas export untuk semua jenis laporan (isi dari LaporanService). */
class LaporanExport extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithCustomValueBinder, WithStyles, WithTitle
{
    /**
     * Nama, tujuan, dan catatan berasal dari form publik. Teks yang diawali = + - @ (atau tab/CR)
     * disimpan sebagai TEKS, bukan formula, supaya tidak dieksekusi Excel (CSV/formula injection).
     */
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && ! is_numeric($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    private int $barisHeader;

    public function __construct(private array $laporan)
    {
        // Baris 1-3: kop, baris 4 kosong, baris 5: header tabel
        $this->barisHeader = 5;
    }

    public function array(): array
    {
        $isi = [
            [config('peminjaman.mitra')],
            [$this->laporan['judul']],
            ['Periode: '.$this->laporan['periode']],
            [''], // baris pemisah; [] kosong dibuang library sehingga header bergeser
            $this->laporan['kolom'],
        ];

        foreach ($this->laporan['baris'] as $baris) {
            $isi[] = $baris;
        }

        $isi[] = [''];
        foreach ($this->laporan['ringkasan'] as $label => $nilai) {
            $isi[] = [$label, $nilai];
        }

        return $isi;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            2 => ['font' => ['bold' => true, 'size' => 12]],
            $this->barisHeader => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E5E7EB']]],
        ];
    }

    public function title(): string
    {
        return ucfirst($this->laporan['jenis']);
    }
}
