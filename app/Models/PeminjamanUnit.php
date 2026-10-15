<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** Unit fisik yang diserahkan pada suatu peminjaman, beserta data pengembalian & denda. */
class PeminjamanUnit extends Pivot
{
    protected $table = 'peminjaman_unit';

    public $incrementing = true;

    protected $fillable = [
        'peminjaman_id', 'unit_barang_id', 'diserahkan_pada', 'dikembalikan_pada',
        'kondisi_kembali', 'hari_terlambat', 'denda', 'catatan',
    ];

    protected $casts = [
        'diserahkan_pada' => 'datetime',
        'dikembalikan_pada' => 'datetime',
        'hari_terlambat' => 'integer',
        'denda' => 'integer',
    ];

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function unitBarang(): BelongsTo
    {
        return $this->belongsTo(UnitBarang::class);
    }
}
