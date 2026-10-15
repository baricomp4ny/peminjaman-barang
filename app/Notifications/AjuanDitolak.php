<?php

namespace App\Notifications;

class AjuanDitolak extends NotifikasiPeminjaman
{
    public function judul(): string
    {
        return 'Ajuan ditolak';
    }

    public function pesan(): string
    {
        return "Ajuan {$this->peminjaman->kode_peminjaman} ditolak. Alasan: {$this->peminjaman->alasan_penolakan}";
    }
}
