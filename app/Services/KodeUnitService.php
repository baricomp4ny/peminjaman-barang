<?php

namespace App\Services;

use App\Enums\KondisiUnit;
use App\Models\Barang;
use App\Models\UnitBarang;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Membuat unit barang dengan kode otomatis: KODE-001, KODE-002, ... */
class KodeUnitService
{
    /**
     * Buat $jumlah unit baru untuk satu jenis barang.
     *
     * @param  array{lokasi_id:int, kondisi:string, tanggal_perolehan:?string}  $data
     * @return Collection<int, UnitBarang>
     */
    public function buatUnit(Barang $barang, array $data, int $jumlah): Collection
    {
        return DB::transaction(function () use ($barang, $data, $jumlah) {
            // Kunci baris barang agar dua admin yang menyimpan bersamaan tidak mendapat nomor yang sama
            Barang::whereKey($barang->id)->lockForUpdate()->first();

            $nomor = $this->nomorTerakhir($barang->kode);
            $unit = collect();

            for ($i = 1; $i <= $jumlah; $i++) {
                $unit->push(UnitBarang::create([
                    'barang_id' => $barang->id,
                    'lokasi_id' => $data['lokasi_id'],
                    'kode_unit' => $this->format($barang->kode, $nomor + $i),
                    'kondisi' => $data['kondisi'],
                    // Unit rusak/hilang tidak boleh berstatus tersedia
                    'status' => KondisiUnit::from($data['kondisi'])->statusUnit(),
                    'tanggal_perolehan' => $data['tanggal_perolehan'] ?? null,
                ]));
            }

            return $unit;
        });
    }

    /** Nomor urut terbesar yang sudah dipakai untuk prefix ini (0 jika belum ada). */
    public function nomorTerakhir(string $prefix): int
    {
        // Ambil angka setelah "PREFIX-" lalu cari maksimum secara numerik (bukan urutan teks)
        return (int) UnitBarang::where('kode_unit', 'like', $prefix.'-%')
            ->pluck('kode_unit')
            ->map(fn ($kode) => (int) substr($kode, strlen($prefix) + 1))
            ->max();
    }

    public function format(string $prefix, int $nomor): string
    {
        return sprintf('%s-%03d', $prefix, $nomor);
    }
}
