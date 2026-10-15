<?php

namespace App\Enums;

use App\Enums\Concerns\DaftarNilai;

enum StatusUnit: string
{
    use DaftarNilai;

    case Tersedia = 'tersedia';
    case Dipinjam = 'dipinjam';
    case Perbaikan = 'perbaikan';
    case Hilang = 'hilang';

    /** Status yang boleh dipilih manual oleh admin ("dipinjam" hanya diatur transaksi). */
    public static function pilihanManual(): array
    {
        return [self::Tersedia, self::Perbaikan, self::Hilang];
    }
}
