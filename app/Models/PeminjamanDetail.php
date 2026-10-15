<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jenis barang + jumlah yang diajukan dalam satu peminjaman. */
class PeminjamanDetail extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_detail';

    protected $fillable = ['peminjaman_id', 'barang_id', 'jumlah'];

    protected $casts = [
        'jumlah' => 'integer',
    ];

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }
}
