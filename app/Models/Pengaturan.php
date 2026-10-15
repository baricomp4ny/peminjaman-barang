<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Aturan bisnis berbentuk key-value yang bisa diubah admin. */
class Pengaturan extends Model
{
    use HasUlids;

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

    /** Nilai pengaturan sebagai angka, contoh: Pengaturan::angka(Pengaturan::DENDA_PER_HARI). */
    public static function angka(string $key): int
    {
        $semua = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());

        return (int) ($semua[$key] ?? self::DEFAULT[$key] ?? 0);
    }

    public static function atur(string $key, int $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
