<?php

namespace App\Enums;

use App\Enums\Concerns\DaftarNilai;

enum StatusAkun: string
{
    use DaftarNilai;

    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';
}
