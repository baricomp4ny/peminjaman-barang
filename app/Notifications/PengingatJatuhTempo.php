<?php

namespace App\Notifications;

/** Dikirim pada hari jatuh tempo (tanggal rencana kembali). */
class PengingatJatuhTempo extends NotifikasiPeminjaman
{
    public function judul(): string
    {
        return 'Hari ini batas pengembalian';
    }

    public function pesan(): string
    {
        return "Hari ini ({$this->peminjaman->tanggal_rencana_kembali->translatedFormat('l, d F Y')}) adalah batas pengembalian "
            ."barang pinjaman {$this->peminjaman->kode_peminjaman}. Mohon kembalikan ke petugas laboratorium hari ini.";
    }
}
