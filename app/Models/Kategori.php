<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'kategori';

    protected $fillable = ['nama'];

    public function barang(): HasMany
    {
        return $this->hasMany(Barang::class);
    }

    /** Alasan kategori tidak boleh dihapus (null = boleh). Dipakai hapus satuan & hapus banyak. */
    public function alasanTidakBisaDihapus(): ?string
    {
        return $this->barang()->exists() ? 'masih dipakai oleh jenis barang' : null;
    }
}
