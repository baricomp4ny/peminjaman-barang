<?php

namespace App\Notifications;

use App\Services\BuktiPeminjamanService;
use App\Services\PeminjamanService;
use Illuminate\Notifications\Messages\MailMessage;

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

    /** Lampirkan PDF bukti peminjaman di email persetujuan. */
    public function toMail(object $notifiable): MailMessage
    {
        $bukti = app(BuktiPeminjamanService::class);

        return $this->denganTautanBatal(parent::toMail($notifiable))
            ->line('Bukti peminjaman terlampir (PDF). Cetak atau tunjukkan saat mengambil alat.')
            ->attachData($bukti->pdf($this->peminjaman), $bukti->namaFile($this->peminjaman), ['mime' => 'application/pdf']);
    }
}
