<?php

namespace App\Notifications;

class AjuanKedaluwarsa extends NotifikasiPeminjaman
{
    public function judul(): string
    {
        return 'Ajuan kedaluwarsa';
    }

    public function pesan(): string
    {
        return "Ajuan {$this->peminjaman->kode_peminjaman} kedaluwarsa karena barang tidak diambil sampai batas waktu. Silakan ajukan ulang bila masih perlu.";
    }
}
