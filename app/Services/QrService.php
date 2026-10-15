<?php

namespace App\Services;

use App\Models\User;
use SimpleSoftwareIO\QrCode\Generator;

/**
 * Pembuatan QR (SVG) dan format isi QR.
 *  - Unit barang : isinya kode_unit apa adanya, contoh PRJ-001.
 *  - Kartu peminjam: PMJ.<id>.<tanda>, tanda = 10 karakter HMAC-SHA256 dengan APP_KEY,
 *    sehingga kartu tidak bisa dibuat hanya dengan mengetahui id/NIM.
 */
class QrService
{
    public const PREFIX_KARTU = 'PMJ';

    public function svg(string $isi, int $ukuran = 160): string
    {
        return (string) (new Generator())
            ->format('svg')
            ->size($ukuran)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($isi);
    }

    public function isiKartu(User $user): string
    {
        return self::PREFIX_KARTU.'.'.$user->id.'.'.$this->tanda($user->id);
    }

    /** Kembalikan id user bila isi kartu valid, null bila bukan kartu / tanda palsu. */
    public function bacaKartu(string $isi): ?int
    {
        if (! preg_match('/^'.self::PREFIX_KARTU.'\.(\d+)\.([a-f0-9]{10})$/i', trim($isi), $m)) {
            return null;
        }

        return hash_equals($this->tanda((int) $m[1]), strtolower($m[2])) ? (int) $m[1] : null;
    }

    public function pakaiFormatKartu(string $isi): bool
    {
        return str_starts_with(strtoupper(trim($isi)), self::PREFIX_KARTU.'.');
    }

    private function tanda(int $userId): string
    {
        return substr(hash_hmac('sha256', 'kartu-peminjam|'.$userId, (string) config('app.key')), 0, 10);
    }
}
