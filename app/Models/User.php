<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_PEMINJAM = 'peminjam';

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_NONAKTIF = 'nonaktif';

    /**
     * Kolom yang boleh diisi massal.
     * Sengaja tanpa `role` dan `status` supaya tidak bisa disisipkan lewat form
     * (misal registrasi publik). Keduanya diisi eksplisit oleh admin/seeder.
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
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

    // ---------- Helper ----------

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPeminjam(): bool
    {
        return $this->role === self::ROLE_PEMINJAM;
    }

    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }

    /** Halaman awal sesuai peran. */
    public function homeRoute(): string
    {
        // Hanya admin yang login; peminjam memakai halaman publik
        return $this->isAdmin() ? route('admin.dashboard') : route('peminjam.katalog.index');
    }

    public function scopePeminjam(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_PEMINJAM);
    }
}
