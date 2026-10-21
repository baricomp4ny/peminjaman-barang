<?php

namespace App\Services;

use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use App\Exceptions\PeminjamanException;
use App\Models\Peminjaman;
use App\Models\RiwayatKondisiUnit;
use App\Models\UnitBarang;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Satu pintu untuk mengubah kondisi/status unit sekaligus mencatat riwayatnya. */
class KondisiUnitService
{
    /**
     * Ubah kondisi & status unit; riwayat hanya ditulis bila ada yang berubah.
     * Dipanggil di dalam transaksi pemanggil (pengembalian / ubah unit).
     */
    public function catat(
        UnitBarang $unit,
        KondisiUnit $kondisi,
        StatusUnit $status,
        string $jenis,
        ?User $admin = null,
        ?string $keterangan = null,
        ?Peminjaman $peminjaman = null,
    ): ?RiwayatKondisiUnit {
        $kondisiLama = $unit->kondisi;
        $statusLama = $unit->status;

        $unit->update(['kondisi' => $kondisi, 'status' => $status]);

        if ($kondisiLama === $kondisi && $statusLama === $status) {
            return null;
        }

        return RiwayatKondisiUnit::create([
            'unit_barang_id' => $unit->id,
            'jenis' => $jenis,
            'kondisi_lama' => $kondisiLama,
            'kondisi_baru' => $kondisi,
            'status_lama' => $statusLama,
            'status_baru' => $status,
            'keterangan' => $keterangan,
            'dicatat_oleh' => $admin?->id,
            'peminjaman_id' => $peminjaman?->id,
        ]);
    }

    /** Unit selesai diperbaiki → kondisi baik & tersedia lagi. Hanya untuk unit berstatus perbaikan. */
    public function perbaikanSelesai(UnitBarang $unit, User $admin, string $keterangan): RiwayatKondisiUnit
    {
        return DB::transaction(function () use ($unit, $admin, $keterangan) {
            // Kunci baris supaya dua klik bersamaan tidak mencatat dua kali
            $unit = UnitBarang::lockForUpdate()->findOrFail($unit->id);

            if ($unit->status !== StatusUnit::Perbaikan) {
                throw new PeminjamanException("Unit {$unit->kode_unit} tidak sedang dalam perbaikan.");
            }

            return $this->catat($unit, KondisiUnit::Baik, StatusUnit::Tersedia, 'perbaikan_selesai', $admin, $keterangan);
        });
    }
}
