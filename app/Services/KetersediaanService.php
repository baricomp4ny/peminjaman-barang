<?php

namespace App\Services;

use App\Enums\KondisiUnit;
use App\Enums\StatusPeminjaman;
use App\Enums\StatusUnit;
use App\Models\PeminjamanDetail;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Menghitung sisa unit per jenis barang pada suatu rentang tanggal.
 *
 * sisa = kapasitas − terpakai
 *  - kapasitas: unit kondisi baik yang statusnya tersedia/dipinjam
 *    (unit perbaikan/hilang tidak bisa dipinjam)
 *  - terpakai : ajuan "disetujui" yang rentangnya bertabrakan (pakai jumlah ajuan)
 *             + pinjaman "dipinjam" yang bertabrakan (pakai unit yang BELUM kembali);
 *               pinjaman yang sudah terlambat dianggap bertabrakan dengan semua tanggal ke depan
 *
 * Tabrakan rentang: mulai_A <= selesai_B DAN selesai_A >= mulai_B.
 * Ajuan "menunggu" sengaja tidak dihitung (belum mengikat stok).
 */
class KetersediaanService
{
    /**
     * @param  array<string>  $barangIds
     * @return array<int, array{kapasitas:int, terpakai:int, sisa:int}> dikunci barang_id
     */
    public function hitung(array $barangIds, $mulai, $selesai, ?string $kecualiPeminjamanId = null): array
    {
        $barangIds = array_values(array_unique(array_map('strval', $barangIds)));
        if ($barangIds === []) {
            return [];
        }

        $mulai = Carbon::parse($mulai)->toDateString();
        $selesai = Carbon::parse($selesai)->toDateString();

        $kapasitas = UnitBarang::query()
            ->whereIn('barang_id', $barangIds)
            ->where('kondisi', KondisiUnit::Baik)
            ->whereIn('status', [StatusUnit::Tersedia, StatusUnit::Dipinjam])
            ->selectRaw('barang_id, COUNT(*) as total')
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id');

        // Ajuan yang sudah disetujui tetapi belum diambil: memesan sejumlah unit sesuai ajuan
        $dipesan = PeminjamanDetail::query()
            ->join('peminjaman', 'peminjaman.id', '=', 'peminjaman_detail.peminjaman_id')
            ->whereIn('peminjaman_detail.barang_id', $barangIds)
            ->where('peminjaman.status', StatusPeminjaman::Disetujui)
            ->when($kecualiPeminjamanId, fn ($q) => $q->where('peminjaman.id', '!=', $kecualiPeminjamanId))
            ->whereDate('peminjaman.tanggal_pinjam', '<=', $selesai)
            ->whereDate('peminjaman.tanggal_rencana_kembali', '>=', $mulai)
            ->selectRaw('peminjaman_detail.barang_id, SUM(peminjaman_detail.jumlah) as total')
            ->groupBy('peminjaman_detail.barang_id')
            ->pluck('total', 'barang_id');

        // Pinjaman berjalan: hitung unit fisik yang belum kembali.
        // Pinjaman TERLAMBAT (rencana kembali < hari ini) memblokir semua tanggal ke depan
        // sampai unitnya benar-benar kembali, karena kapan kembalinya tidak bisa dipastikan.
        $hariIni = today()->toDateString();
        $dipinjam = PeminjamanUnit::query()
            ->join('peminjaman', 'peminjaman.id', '=', 'peminjaman_unit.peminjaman_id')
            ->join('unit_barang', 'unit_barang.id', '=', 'peminjaman_unit.unit_barang_id')
            ->whereIn('unit_barang.barang_id', $barangIds)
            ->where('peminjaman.status', StatusPeminjaman::Dipinjam)
            ->whereNull('peminjaman_unit.dikembalikan_pada')
            ->when($kecualiPeminjamanId, fn ($q) => $q->where('peminjaman.id', '!=', $kecualiPeminjamanId))
            ->whereDate('peminjaman.tanggal_pinjam', '<=', $selesai)
            ->where(fn ($q) => $q->whereDate('peminjaman.tanggal_rencana_kembali', '>=', $mulai)
                ->orWhereDate('peminjaman.tanggal_rencana_kembali', '<', $hariIni))
            ->selectRaw('unit_barang.barang_id, COUNT(*) as total')
            ->groupBy('unit_barang.barang_id')
            ->pluck('total', 'barang_id');

        $hasil = [];
        foreach ($barangIds as $id) {
            $kap = (int) ($kapasitas[$id] ?? 0);
            $pakai = (int) ($dipesan[$id] ?? 0) + (int) ($dipinjam[$id] ?? 0);
            $hasil[$id] = ['kapasitas' => $kap, 'terpakai' => $pakai, 'sisa' => max(0, $kap - $pakai)];
        }

        return $hasil;
    }

    /** Sisa unit satu jenis barang pada rentang tanggal. */
    public function sisa(string $barangId, $mulai, $selesai, ?string $kecualiPeminjamanId = null): int
    {
        return $this->hitung([$barangId], $mulai, $selesai, $kecualiPeminjamanId)[$barangId]['sisa'];
    }

    /**
     * Cek apakah seluruh detail ajuan bisa dipenuhi.
     *
     * @param  array<string, int>  $permintaan  barang_id => jumlah
     * @return array<string, int> barang_id => sisa, hanya untuk barang yang KURANG
     */
    public function kekurangan(array $permintaan, CarbonInterface|string $mulai, CarbonInterface|string $selesai, ?string $kecualiPeminjamanId = null): array
    {
        $stok = $this->hitung(array_keys($permintaan), $mulai, $selesai, $kecualiPeminjamanId);

        return collect($permintaan)
            ->filter(fn ($jumlah, $barangId) => $jumlah > $stok[$barangId]['sisa'])
            ->map(fn ($jumlah, $barangId) => $stok[$barangId]['sisa'])
            ->all();
    }
}
