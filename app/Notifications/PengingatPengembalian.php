<?php

namespace App\Notifications;

/** H-1 sebelum tanggal rencana kembali. */
class PengingatPengembalian extends NotifikasiPeminjaman
{
    public function judul(): string
    {
        return 'Pengingat pengembalian besok';
    }

    public function pesan(): string
    {
        return "Barang pinjaman {$this->peminjaman->kode_peminjaman} harus dikembalikan besok, "
            .$this->peminjaman->tanggal_rencana_kembali->translatedFormat('l, d F Y').'.';
    }
}
