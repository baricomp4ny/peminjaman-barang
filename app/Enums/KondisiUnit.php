<?php

namespace App\Enums;

use App\Enums\Concerns\DaftarNilai;

enum KondisiUnit: string
{
    use DaftarNilai;

    case Baik = 'baik';
    case Rusak = 'rusak';
    case Hilang = 'hilang';

    /** Status unit yang sesuai kondisi: rusak → perbaikan, hilang → hilang, baik → tersedia. */
    public function statusUnit(): StatusUnit
    {
        return match ($this) {
            self::Rusak => StatusUnit::Perbaikan,
            self::Hilang => StatusUnit::Hilang,
            self::Baik => StatusUnit::Tersedia,
        };
    }
}
