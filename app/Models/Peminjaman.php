<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peminjaman extends Model
{
    use HasFactory;

    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_DIPINJAM = 'dipinjam';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DIBATALKAN = 'dibatalkan';
    public const STATUS_KEDALUWARSA = 'kedaluwarsa';

    public const STATUS = [
        self::STATUS_MENUNGGU, self::STATUS_DISETUJUI, self::STATUS_DITOLAK, self::STATUS_DIPINJAM,
        self::STATUS_SELESAI, self::STATUS_DIBATALKAN, self::STATUS_KEDALUWARSA,
    ];

    protected $table = 'peminjaman';

    protected $fillable = [
        'kode_peminjaman', 'user_id', 'tujuan', 'tanggal_pinjam', 'tanggal_rencana_kembali', 'status',
        'alasan_penolakan', 'diverifikasi_oleh', 'diverifikasi_pada', 'diserahkan_pada',
    ];

    protected $casts = [
        'tanggal_pinjam' => 'date',
        'tanggal_rencana_kembali' => 'date',
        'diverifikasi_pada' => 'datetime',
        'diserahkan_pada' => 'datetime',
    ];

    // ---------- Relasi ----------

    public function peminjam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(PeminjamanDetail::class);
    }

    /** Baris serah-terima per unit (berisi data pengembalian & denda). */
    public function peminjamanUnit(): HasMany
    {
        return $this->hasMany(PeminjamanUnit::class);
    }

    public function unit(): BelongsToMany
    {
        return $this->belongsToMany(UnitBarang::class, 'peminjaman_unit')
            ->using(PeminjamanUnit::class)
            ->withPivot(['diserahkan_pada', 'dikembalikan_pada', 'kondisi_kembali', 'hari_terlambat', 'denda', 'catatan'])
            ->withTimestamps();
    }

    // ---------- Scope ----------

    /**
     * Peminjaman yang terlambat: masih dipinjam, sudah lewat tanggal rencana kembali,
     * dan masih ada unit yang belum dikembalikan. Dihitung otomatis, bukan kolom.
     */
    public function scopeTerlambat(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DIPINJAM)
            ->whereDate('tanggal_rencana_kembali', '<', now()->toDateString())
            ->whereHas('peminjamanUnit', fn (Builder $q) => $q->whereNull('dikembalikan_pada'));
    }

    /**
     * Ajuan yang rentang tanggalnya bertabrakan dengan [$mulai, $selesai].
     * Rumus: mulai_A <= selesai_B dan selesai_A >= mulai_B.
     */
    public function scopeBertabrakan(Builder $query, $mulai, $selesai): Builder
    {
        return $query->whereDate('tanggal_pinjam', '<=', $selesai)
            ->whereDate('tanggal_rencana_kembali', '>=', $mulai);
    }

    // ---------- Helper ----------

    public function isTerlambat(): bool
    {
        if ($this->status !== self::STATUS_DIPINJAM || ! $this->tanggal_rencana_kembali->lt(today())) {
            return false;
        }

        return $this->peminjamanUnit()->whereNull('dikembalikan_pada')->exists();
    }

    public function totalDenda(): int
    {
        return (int) $this->peminjamanUnit()->sum('denda');
    }
}
