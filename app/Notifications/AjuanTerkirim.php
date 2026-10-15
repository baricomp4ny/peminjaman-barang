<?php

namespace App\Notifications;

/** Ke peminjam: bukti ajuan terkirim berisi kode peminjaman & tautan cek status. */
class AjuanTerkirim extends NotifikasiPeminjaman
{
    public function judul(): string
    {
        return 'Ajuan peminjaman terkirim';
    }

    public function pesan(): string
    {
        return "Ajuan Anda tercatat dengan kode {$this->peminjaman->kode_peminjaman} untuk {$this->rentang()} dan sedang menunggu verifikasi admin. "
            .'Simpan kode ini; cek status kapan saja dengan kode ini dan nomor identitas Anda.';
    }

    protected function rincian(): array
    {
        return $this->peminjaman->detail()->with('barang')->get()
            ->map(fn ($d) => "• {$d->barang->nama} × {$d->jumlah}")
            ->all();
    }
}
