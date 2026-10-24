<?php

namespace App\Models;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Admin (login) dan peminjam (tanpa login, dicocokkan lewat nomor_identitas).
 */
class User extends Authenticatable
{
    use HasFactory, HasUlids, Notifiable;

    /**
     * Sengaja tanpa `role` dan `status` supaya tidak bisa disisipkan lewat form;
     * keduanya diisi eksplisit (forceFill) oleh admin/seeder.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nomor_identitas',
        'unit',
        'no_hp',
    ];

    /** @var array<int, string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => Peran::class,
        'status' => StatusAkun::class,
    ];

    // ---------- Relasi ----------

    /** Ajuan/peminjaman milik user ini (sebagai peminjam). */
    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'user_id');
    }

    /** Ajuan yang diverifikasi user ini (sebagai admin). */
    public function verifikasi(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'diverifikasi_oleh');
    }

    // ---------- Scope ----------

    public function scopePeminjam(Builder $query): Builder
    {
        return $query->where('role', Peran::Peminjam);
    }

    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('role', Peran::Admin);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', StatusAkun::Aktif);
    }

    /**
     * Tambah kolom jumlah_terlambat: banyaknya peminjaman yang pernah terlambat
     * (ada unit kembali terlambat) atau yang saat ini sedang terlambat.
     */
    public function scopeWithJumlahTerlambat(Builder $query): Builder
    {
        return $query->withCount(['peminjaman as jumlah_terlambat' => fn (Builder $q) => $q->where(fn (Builder $w) => $w
            ->whereHas('peminjamanUnit', fn (Builder $u) => $u->where('hari_terlambat', '>', 0))
            ->orWhere(fn (Builder $o) => $o->terlambat()))]);
    }

    // ---------- Helper ----------

    public function isAdmin(): bool
    {
        return $this->role === Peran::Admin;
    }

    public function isPeminjam(): bool
    {
        return $this->role === Peran::Peminjam;
    }

    public function isAktif(): bool
    {
        return $this->status === StatusAkun::Aktif;
    }

    /**
     * Alasan akun tidak boleh dihapus (null = boleh).
     * Peminjam: punya riwayat. Admin: akun sendiri, pernah memverifikasi, atau admin aktif terakhir.
     */
    public function alasanTidakBisaDihapus(?User $oleh = null): ?string
    {
        if (! $this->isAdmin()) {
            return $this->peminjaman()->exists() ? 'memiliki riwayat peminjaman' : null;
        }

        return match (true) {
            $oleh !== null && $this->is($oleh) => 'akun Anda sendiri',
            // Disimpan demi jejak audit; cukup dinonaktifkan
            $this->verifikasi()->exists() => 'pernah memverifikasi ajuan',
            $this->isAktif() && self::admin()->aktif()->count() <= 1 => 'harus ada minimal satu admin aktif',
            default => null,
        };
    }
}
