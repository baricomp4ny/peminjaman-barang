<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu unit fisik barang; kode_unit adalah isi QR-nya. */
class UnitBarang extends Model
{
    use HasFactory;

    public const KONDISI_BAIK = 'baik';
    public const KONDISI_RUSAK = 'rusak';
    public const KONDISI_HILANG = 'hilang';

    public const STATUS_TERSEDIA = 'tersedia';
    public const STATUS_DIPINJAM = 'dipinjam';
    public const STATUS_PERBAIKAN = 'perbaikan';
    public const STATUS_HILANG = 'hilang';

    protected $table = 'unit_barang';

    protected $fillable = ['barang_id', 'lokasi_id', 'kode_unit', 'kondisi', 'status', 'tanggal_perolehan'];

    protected $casts = [
        'tanggal_perolehan' => 'date',
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

    public function peminjaman(): BelongsToMany
    {
        return $this->belongsToMany(Peminjaman::class, 'peminjaman_unit')
            ->using(PeminjamanUnit::class)
            ->withPivot(['diserahkan_pada', 'dikembalikan_pada', 'kondisi_kembali', 'hari_terlambat', 'denda', 'catatan'])
            ->withTimestamps();
    }

    /** Status yang sesuai untuk suatu kondisi: rusak → perbaikan, hilang → hilang, baik → tersedia. */
    public static function statusDariKondisi(string $kondisi): string
    {
        return match ($kondisi) {
            self::KONDISI_RUSAK => self::STATUS_PERBAIKAN,
            self::KONDISI_HILANG => self::STATUS_HILANG,
            default => self::STATUS_TERSEDIA,
        };
    }

    /** Unit boleh dipinjam hanya jika kondisinya baik dan statusnya tersedia. */
    public function bisaDipinjam(): bool
    {
        return $this->kondisi === self::KONDISI_BAIK && $this->status === self::STATUS_TERSEDIA;
    }
}
