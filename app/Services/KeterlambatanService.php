<?php

namespace App\Services;

use App\Models\PeminjamanUnit;
use App\Models\Pengaturan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Keterlambatan & penangguhan (pengganti denda).
 * Peminjam yang mengembalikan terlambat tidak bisa mengajukan selama
 * lama_penangguhan_hari, dihitung sejak tanggal alat dikembalikan terlambat.
 */
class KeterlambatanService
{
    /** Jumlah hari terlambat, dihitung per tanggal (jam diabaikan). */
    public function hariTerlambat(CarbonInterface $rencanaKembali, CarbonInterface $dikembalikan): int
    {
        $rencana = Carbon::parse($rencanaKembali)->startOfDay();
        $kembali = Carbon::parse($dikembalikan)->startOfDay();

        return max(0, (int) $rencana->diffInDays($kembali, false));
    }

    public function lamaPenangguhan(): int
    {
        return Pengaturan::angka(Pengaturan::LAMA_PENANGGUHAN_HARI);
    }

    /** Tanggal pengembalian terlambat terakhir milik peminjam (null bila belum pernah). */
    public function kembaliTerlambatTerakhir(User $peminjam): ?Carbon
    {
        $waktu = PeminjamanUnit::query()
            ->where('hari_terlambat', '>', 0)
            ->whereNotNull('dikembalikan_pada')
            ->whereHas('peminjaman', fn ($q) => $q->where('user_id', $peminjam->id))
            ->max('dikembalikan_pada');

        return $waktu ? Carbon::parse($waktu)->startOfDay() : null;
    }

    /**
     * Tanggal peminjam boleh mengajukan lagi, atau null bila tidak sedang ditangguhkan.
     * Contoh: kembali terlambat 12 Okt + 7 hari → bisa mengajukan mulai 19 Okt.
     */
    public function penangguhanBerakhir(User $peminjam, ?CarbonInterface $hariIni = null): ?Carbon
    {
        $lama = $this->lamaPenangguhan();
        $terakhir = $lama > 0 ? $this->kembaliTerlambatTerakhir($peminjam) : null;
        if (! $terakhir) {
            return null;
        }

        $berakhir = $terakhir->copy()->addDays($lama);

        return $berakhir->greaterThan(Carbon::parse($hariIni ?? today())->startOfDay()) ? $berakhir : null;
    }

    /** Pesan alasan penangguhan untuk form ajuan & panel verifikasi. */
    public function pesanPenangguhan(User $peminjam): ?string
    {
        $berakhir = $this->penangguhanBerakhir($peminjam);
        if (! $berakhir) {
            return null;
        }

        return 'Anda mengembalikan alat terlambat pada '.$this->kembaliTerlambatTerakhir($peminjam)->translatedFormat('d M Y')
            .'. Sesuai aturan penangguhan '.$this->lamaPenangguhan().' hari, pengajuan baru dapat dilakukan mulai '
            .$berakhir->translatedFormat('d M Y').'.';
    }
}
