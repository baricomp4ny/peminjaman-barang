<?php

namespace App\Enums;

use App\Enums\Concerns\DaftarNilai;

enum StatusPeminjaman: string
{
    use DaftarNilai;

    case Menunggu = 'menunggu';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Dipinjam = 'dipinjam';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';
    case Kedaluwarsa = 'kedaluwarsa';

    /** Peminjam boleh membatalkan selama alat belum diambil. */
    public function bisaDibatalkan(): bool
    {
        return in_array($this, [self::Menunggu, self::Disetujui], true);
    }
}
