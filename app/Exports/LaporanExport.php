<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Satu kelas export untuk semua jenis laporan (isi dari LaporanService). */
class LaporanExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
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
            [],
            $this->laporan['kolom'],
        ];

        foreach ($this->laporan['baris'] as $baris) {
            $isi[] = $baris;
        }

        $isi[] = [];
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
