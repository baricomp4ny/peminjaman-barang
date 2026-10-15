<?php

namespace App\Notifications;

use App\Models\Pengaturan;

/** H-1 sebelum tanggal rencana kembali. */
class PengingatPengembalian extends NotifikasiPeminjaman
{
    public function judul(): string
    {
        return 'Pengingat pengembalian besok';
    }

    public function pesan(): string
    {
        $tarif = Pengaturan::angka(Pengaturan::DENDA_PER_HARI);
        $denda = $tarif > 0 ? ' Keterlambatan dikenakan denda '.rupiah($tarif).' per unit per hari.' : '';

        return "Barang pinjaman {$this->peminjaman->kode_peminjaman} harus dikembalikan besok, "
            .$this->peminjaman->tanggal_rencana_kembali->translatedFormat('l, d F Y').'.'.$denda;
    }
}
