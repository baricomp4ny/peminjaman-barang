<?php

namespace App\Services;

use App\Models\Peminjaman;
use App\Models\Pengaturan;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** Perhitungan keterlambatan dan denda: hari terlambat × tarif per unit per hari. */
class DendaService
{
    public function tarif(): int
    {
        return Pengaturan::angka(Pengaturan::DENDA_PER_HARI);
    }

    /** Jumlah hari terlambat, dihitung per tanggal (jam diabaikan). */
    public function hariTerlambat(CarbonInterface $rencanaKembali, CarbonInterface $dikembalikan): int
    {
        $rencana = Carbon::parse($rencanaKembali)->startOfDay();
        $kembali = Carbon::parse($dikembalikan)->startOfDay();

        return max(0, (int) $rencana->diffInDays($kembali, false));
    }

    /**
     * Denda untuk SATU unit.
     *
     * @return array{hari:int, denda:int}
     */
    public function hitung(CarbonInterface $rencanaKembali, CarbonInterface $dikembalikan, ?int $tarif = null): array
    {
        $hari = $this->hariTerlambat($rencanaKembali, $dikembalikan);

        return ['hari' => $hari, 'denda' => $hari * ($tarif ?? $this->tarif())];
    }

    /** Perkiraan denda berjalan untuk unit yang belum kembali (dipakai di tampilan). */
    public function perkiraan(Peminjaman $peminjaman, ?CarbonInterface $hariIni = null): int
    {
        $belumKembali = $peminjaman->peminjamanUnit->whereNull('dikembalikan_pada')->count();
        $hitung = $this->hitung($peminjaman->tanggal_rencana_kembali, $hariIni ?? today());

        return $belumKembali * $hitung['denda'];
    }
}
