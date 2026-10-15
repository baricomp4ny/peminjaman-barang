<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Jenis barang, misal "Proyektor". Unit fisiknya ada di UnitBarang. */
class Barang extends Model
{
    use HasFactory;

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

    /** Unit yang statusnya tersedia saat ini (belum memperhitungkan ajuan yang sudah disetujui). */
    public function unitTersedia(): HasMany
    {
        return $this->unit()->where('status', UnitBarang::STATUS_TERSEDIA);
    }

    public function peminjamanDetail(): HasMany
    {
        return $this->hasMany(PeminjamanDetail::class);
    }
}
