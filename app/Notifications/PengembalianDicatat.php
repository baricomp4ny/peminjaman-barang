<?php

namespace App\Notifications;

use App\Enums\StatusPeminjaman;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\Pengaturan;
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
        return $this->peminjaman->status === StatusPeminjaman::Selesai ? 'Pengembalian selesai' : 'Pengembalian sebagian dicatat';
    }

    public function pesan(): string
    {
        $kode = $this->unit->map(fn ($u) => $u->unitBarang->kode_unit)->join(', ');
        $lama = Pengaturan::angka(Pengaturan::LAMA_PENANGGUHAN_HARI);
        // Kembali terlambat → beri tahu masa penangguhan pengajuan
        $penangguhan = $this->unit->max('hari_terlambat') > 0 && $lama > 0
            ? " Karena terlambat, pengajuan baru dapat dilakukan mulai {$this->unit->max('dikembalikan_pada')->copy()->startOfDay()->addDays($lama)->translatedFormat('d F Y')}."
            : '';

        return "Pengembalian unit {$kode} untuk {$this->peminjaman->kode_peminjaman} sudah dicatat.{$penangguhan}";
    }

    protected function rincian(): array
    {
        return $this->unit->map(fn ($u) => "• {$u->unitBarang->kode_unit}: kondisi {$u->kondisi_kembali->value}"
            .($u->hari_terlambat > 0 ? ", terlambat {$u->hari_terlambat} hari" : ''))->all();
    }
}
