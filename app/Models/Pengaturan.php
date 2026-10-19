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

    /** Lama penangguhan pengajuan (hari) setelah mengembalikan terlambat; 0 = tanpa penangguhan. */
    public const LAMA_PENANGGUHAN_HARI = 'lama_penangguhan_hari';

    public const BATAS_AMBIL_HARI = 'batas_ambil_hari';

    /** Nilai bawaan bila belum ada di database. */
    public const DEFAULT = [
        self::LAMA_PINJAM_MAKS => 7,
        self::UNIT_MAKS_PER_AJUAN => 3,
        self::LAMA_PENANGGUHAN_HARI => 7,
        self::BATAS_AMBIL_HARI => 1,
    ];

    /** Syarat peminjaman (teks) yang wajib disetujui di form ajuan. */
    public const SYARAT_PEMINJAMAN = 'syarat_peminjaman';

    /** Nilai bawaan pengaturan berupa teks. */
    public const TEKS_DEFAULT = [
        self::SYARAT_PEMINJAMAN => '1. Alat hanya digunakan untuk kegiatan akademik di lingkungan Laboratorium Kultur Jaringan.
'
            .'2. Peminjam wajib menunjukkan kartu identitas asli saat pengambilan.
'
            .'3. Alat dikembalikan paling lambat pada tanggal rencana kembali dalam kondisi bersih dan lengkap.
'
            .'4. Kerusakan atau kehilangan alat menjadi tanggung jawab peminjam dan wajib dilaporkan kepada petugas.
'
            .'5. Keterlambatan pengembalian menyebabkan penangguhan pengajuan peminjaman berikutnya.',
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

    /** Nilai pengaturan sebagai angka, contoh: Pengaturan::angka(Pengaturan::LAMA_PINJAM_MAKS). */
    public static function angka(string $key): int
    {
        $semua = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());

        return (int) ($semua[$key] ?? self::DEFAULT[$key] ?? 0);
    }

    /** Nilai pengaturan sebagai teks, contoh: Pengaturan::teks(Pengaturan::SYARAT_PEMINJAMAN). */
    public static function teks(string $key): string
    {
        $semua = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());

        return (string) ($semua[$key] ?? self::TEKS_DEFAULT[$key] ?? '');
    }

    public static function atur(string $key, int|string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
