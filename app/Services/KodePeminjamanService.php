<?php

namespace App\Services;

use App\Models\Peminjaman;

/** Membuat kode peminjaman PJM-YYYYMMDD-0001 (nomor urut per hari). */
class KodePeminjamanService
{
    /** Panggil di dalam DB::transaction agar penguncian berlaku. */
    public function berikutnya(): string
    {
        $prefix = 'PJM-'.now()->format('Ymd').'-';

        // Kunci baris kode hari ini supaya dua ajuan bersamaan tidak mendapat nomor sama
        $terakhir = Peminjaman::where('kode_peminjaman', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('kode_peminjaman')
            ->map(fn ($kode) => (int) substr($kode, strlen($prefix)))
            ->max();

        return $prefix.sprintf('%04d', ((int) $terakhir) + 1);
    }
}
