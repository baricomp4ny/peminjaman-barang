<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Jenis alat, misal "Mikroskop Stereo". Unit fisiknya ada di UnitBarang. */
class Barang extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'barang';

    protected $fillable = ['kategori_id', 'kode', 'nama', 'deskripsi', 'foto'];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function unit(): HasMany
    {
        return $this->hasMany(UnitBarang::class);
    }

    public function peminjamanDetail(): HasMany
    {
        return $this->hasMany(PeminjamanDetail::class);
    }
}
