<?php

namespace App\Notifications;

use App\Models\Peminjaman;
use App\Models\Pengaturan;
use Carbon\CarbonInterface;

/** Dikirim setiap hari selama pinjaman terlambat (satu kali per tanggal). */
class PengingatTerlambat extends NotifikasiPeminjaman
{
    public function __construct(Peminjaman $peminjaman, public CarbonInterface $tanggal, public int $hari)
    {
        parent::__construct($peminjaman);
    }

    public function judul(): string
    {
        return "Pinjaman terlambat {$this->hari} hari";
    }

    public function pesan(): string
    {
        $lama = Pengaturan::angka(Pengaturan::LAMA_PENANGGUHAN_HARI);
        $penangguhan = $lama > 0 ? " Setelah dikembalikan, Anda tidak dapat mengajukan peminjaman baru selama {$lama} hari." : '';

        return "Barang pinjaman {$this->peminjaman->kode_peminjaman} seharusnya dikembalikan pada "
            .$this->peminjaman->tanggal_rencana_kembali->translatedFormat('d F Y')
            .", sekarang sudah terlambat {$this->hari} hari. Segera kembalikan ke petugas laboratorium.".$penangguhan;
    }

    public function toArray(object $notifiable): array
    {
        // Tanggal dipakai untuk mencegah pengingat dobel di hari yang sama
        return array_merge(parent::toArray($notifiable), ['tanggal' => $this->tanggal->toDateString()]);
    }
}
