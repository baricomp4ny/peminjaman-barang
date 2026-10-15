<?php

namespace App\Notifications;

/** Ke admin: ada ajuan baru yang perlu diverifikasi. */
class AjuanBaru extends NotifikasiPeminjaman
{
    public function judul(): string
    {
        return 'Ajuan peminjaman baru';
    }

    public function pesan(): string
    {
        return "{$this->peminjaman->peminjam->name} mengajukan peminjaman {$this->peminjaman->kode_peminjaman} untuk {$this->rentang()}.";
    }

    protected function rincian(): array
    {
        return $this->peminjaman->detail()->with('barang')->get()
            ->map(fn ($d) => "• {$d->barang->nama} × {$d->jumlah}")
            ->prepend('Tujuan: '.$this->peminjaman->tujuan)
            ->all();
    }
}
