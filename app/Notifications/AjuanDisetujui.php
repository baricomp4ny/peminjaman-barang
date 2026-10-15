<?php

namespace App\Notifications;

use App\Services\PeminjamanService;

class AjuanDisetujui extends NotifikasiPeminjaman
{
    public function judul(): string
    {
        return 'Ajuan disetujui';
    }

    public function pesan(): string
    {
        $batas = app(PeminjamanService::class)->batasAmbil($this->peminjaman)->translatedFormat('d F Y');

        return "Ajuan {$this->peminjaman->kode_peminjaman} disetujui. Ambil barang mulai "
            .$this->peminjaman->tanggal_pinjam->translatedFormat('d F Y')." paling lambat {$batas}, lewat dari itu ajuan kedaluwarsa.";
    }
}
