<?php

namespace App\Enums;

use App\Enums\Concerns\DaftarNilai;

enum Peran: string
{
    use DaftarNilai;

    case Admin = 'admin';
    case Peminjam = 'peminjam';
}
