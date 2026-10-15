<?php

namespace App\Models;

use App\Enums\KondisiUnit;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Unit fisik yang diserahkan pada suatu peminjaman, beserta data pengembalian & denda.
 * Waktu serah-terima memakai peminjaman.diserahkan_pada.
 */
class PeminjamanUnit extends Model
{
    use HasUlids;

    protected $table = 'peminjaman_unit';

    protected $fillable = [
        'peminjaman_id', 'unit_barang_id', 'dikembalikan_pada',
        'kondisi_kembali', 'hari_terlambat', 'denda', 'catatan',
    ];

    protected $casts = [
        'dikembalikan_pada' => 'datetime',
        'kondisi_kembali' => KondisiUnit::class,
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
