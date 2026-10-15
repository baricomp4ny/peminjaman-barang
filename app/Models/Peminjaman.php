<?php

namespace App\Models;

use App\Enums\StatusPeminjaman;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peminjaman extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'peminjaman';

    protected $fillable = [
        'kode_peminjaman', 'user_id', 'tujuan', 'tanggal_pinjam', 'tanggal_rencana_kembali', 'status',
        'alasan_penolakan', 'diverifikasi_oleh', 'diverifikasi_pada', 'diserahkan_pada',
    ];

    protected $casts = [
        'status' => StatusPeminjaman::class,
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

    /** Unit yang diserahkan (berisi data pengembalian & denda). */
    public function peminjamanUnit(): HasMany
    {
        return $this->hasMany(PeminjamanUnit::class);
    }

    // ---------- Scope ----------

    /**
     * Peminjaman yang terlambat: masih dipinjam, sudah lewat tanggal rencana kembali,
     * dan masih ada unit yang belum dikembalikan. Dihitung otomatis, bukan kolom.
     */
    public function scopeTerlambat(Builder $query): Builder
    {
        return $query->where('status', StatusPeminjaman::Dipinjam)
            ->whereDate('tanggal_rencana_kembali', '<', today()->toDateString())
            ->whereHas('peminjamanUnit', fn (Builder $q) => $q->whereNull('dikembalikan_pada'));
    }

    // ---------- Helper ----------

    public function isTerlambat(): bool
    {
        if ($this->status !== StatusPeminjaman::Dipinjam || ! $this->tanggal_rencana_kembali->lt(today())) {
            return false;
        }

        // Pakai relasi yang sudah dimuat (eager loading) bila ada, supaya tidak query per baris
        return $this->relationLoaded('peminjamanUnit')
            ? $this->peminjamanUnit->whereNull('dikembalikan_pada')->isNotEmpty()
            : $this->peminjamanUnit()->whereNull('dikembalikan_pada')->exists();
    }

    /** Hari terlambat sampai hari ini (0 bila belum jatuh tempo / tidak sedang dipinjam). */
    public function hariTerlambatBerjalan(): int
    {
        if ($this->status !== StatusPeminjaman::Dipinjam) {
            return 0;
        }

        return max(0, (int) $this->tanggal_rencana_kembali->diffInDays(today(), false));
    }

    public function totalDenda(): int
    {
        return (int) $this->peminjamanUnit()->sum('denda');
    }

    public function bisaDibatalkan(): bool
    {
        return $this->status->bisaDibatalkan();
    }

    /** Ringkasan alat, contoh: "Mikroskop Stereo ×1, pH Meter Digital ×2". */
    public function ringkasanBarang(): string
    {
        return $this->detail->map(fn ($d) => $d->barang->nama.' ×'.$d->jumlah)->join(', ');
    }
}
