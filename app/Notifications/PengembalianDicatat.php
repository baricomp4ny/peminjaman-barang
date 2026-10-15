<?php

namespace App\Notifications;

use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use Illuminate\Support\Collection;

class PengembalianDicatat extends NotifikasiPeminjaman
{
    /** @param  Collection<int, PeminjamanUnit>  $unit */
    public function __construct(Peminjaman $peminjaman, public Collection $unit)
    {
        parent::__construct($peminjaman);
    }

    public function judul(): string
    {
        return $this->peminjaman->status === Peminjaman::STATUS_SELESAI ? 'Pengembalian selesai' : 'Pengembalian sebagian dicatat';
    }

    public function pesan(): string
    {
        $kode = $this->unit->map(fn ($u) => $u->unitBarang->kode_unit)->join(', ');
        $denda = (int) $this->unit->sum('denda');
        $teksDenda = $denda > 0 ? ' Denda keterlambatan: Rp'.number_format($denda, 0, ',', '.').'.' : '';

        return "Pengembalian unit {$kode} untuk {$this->peminjaman->kode_peminjaman} sudah dicatat.{$teksDenda}";
    }

    protected function rincian(): array
    {
        return $this->unit->map(fn ($u) => "• {$u->unitBarang->kode_unit}: kondisi {$u->kondisi_kembali}"
            .($u->hari_terlambat > 0 ? ", terlambat {$u->hari_terlambat} hari" : ''))->all();
    }
}
