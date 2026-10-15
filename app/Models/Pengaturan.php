<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Aturan bisnis berbentuk key-value yang bisa diubah admin. */
class Pengaturan extends Model
{
    public const LAMA_PINJAM_MAKS = 'lama_pinjam_maks';
    public const UNIT_MAKS_PER_AJUAN = 'unit_maks_per_ajuan';
    public const DENDA_PER_HARI = 'denda_per_hari';
    public const BATAS_AMBIL_HARI = 'batas_ambil_hari';

    /** Nilai bawaan bila belum ada di database. */
    public const DEFAULT = [
        self::LAMA_PINJAM_MAKS => 7,
        self::UNIT_MAKS_PER_AJUAN => 3,
        self::DENDA_PER_HARI => 1000,
        self::BATAS_AMBIL_HARI => 1,
    ];

    private const CACHE_KEY = 'pengaturan.semua';

    protected $table = 'pengaturan';

    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        // Hapus cache setiap ada perubahan supaya nilai terbaru langsung dipakai
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** Ambil nilai pengaturan, contoh: Pengaturan::nilai(Pengaturan::DENDA_PER_HARI). */
    public static function nilai(string $key, $default = null)
    {
        $semua = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());

        return $semua[$key] ?? $default ?? (self::DEFAULT[$key] ?? null);
    }

    public static function atur(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
