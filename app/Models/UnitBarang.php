<?php

namespace App\Models;

use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu unit fisik alat; kode_unit adalah isi QR-nya. */
class UnitBarang extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'unit_barang';

    protected $fillable = ['barang_id', 'lokasi_id', 'kode_unit', 'kondisi', 'status', 'tanggal_perolehan'];

    protected $casts = [
        'kondisi' => KondisiUnit::class,
        'status' => StatusUnit::class,
        'tanggal_perolehan' => 'date',
    ];

    /** Nilai bawaan unit baru. */
    protected $attributes = [
        'kondisi' => 'baik',
        'status' => 'tersedia',
    ];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function peminjamanUnit(): HasMany
    {
        return $this->hasMany(PeminjamanUnit::class);
    }

    /** Unit boleh dipinjam hanya jika kondisinya baik dan statusnya tersedia. */
    public function bisaDipinjam(): bool
    {
        return $this->kondisi === KondisiUnit::Baik && $this->status === StatusUnit::Tersedia;
    }
}
