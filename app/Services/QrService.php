<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Generator;

/**
 * Pembuatan QR (SVG).
 * Isi QR unit = kode_unit (contoh PRJ-001); isi QR peminjaman = kode_peminjaman (PJM-…).
 */
class QrService
{
    public function svg(string $isi, int $ukuran = 160): string
    {
        return (string) (new Generator)
            ->format('svg')
            ->size($ukuran)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($isi);
    }
}
