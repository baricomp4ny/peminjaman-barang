<?php

namespace App\Support;

use App\Models\Peminjaman;

/** Tautan wa.me untuk menghubungi peminjam (tanpa API; admin mengirim sendiri dari WhatsApp-nya). */
class WhatsApp
{
    /** Ubah nomor lokal ke format internasional tanpa tanda +, contoh 0812-3456 → 628123456. Null bila tidak valid. */
    public static function nomor(?string $noHp): ?string
    {
        $angka = preg_replace('/\D/', '', (string) $noHp);

        $angka = match (true) {
            str_starts_with($angka, '62') => $angka,
            str_starts_with($angka, '0') => '62'.substr($angka, 1),
            str_starts_with($angka, '8') => '62'.$angka,
            default => '',
        };

        // Nomor HP Indonesia: 62 + 8–13 digit
        return preg_match('/^628\d{7,12}$/', $angka) ? $angka : null;
    }

    public static function tautan(?string $noHp, string $pesan): ?string
    {
        $nomor = self::nomor($noHp);

        return $nomor ? 'https://wa.me/'.$nomor.'?text='.rawurlencode($pesan) : null;
    }

    /** Pesan pengingat untuk peminjaman yang terlambat dikembalikan. */
    public static function pesanTerlambat(Peminjaman $p): string
    {
        return implode("\n", [
            "Halo {$p->peminjam->name},",
            '',
            'Kami dari '.config('peminjaman.mitra').' ingin mengingatkan bahwa peminjaman berikut sudah melewati tanggal pengembalian:',
            "• Kode: {$p->kode_peminjaman}",
            "• Alat: {$p->ringkasanBarang()}",
            '• Jatuh tempo: '.$p->tanggal_rencana_kembali->translatedFormat('d F Y'),
            "• Terlambat: {$p->hariTerlambatBerjalan()} hari",
            '',
            'Mohon segera mengembalikan alat ke laboratorium. Terima kasih.',
        ]);
    }
}
