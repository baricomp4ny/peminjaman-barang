<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lokasi extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'lokasi';

    protected $fillable = ['nama', 'keterangan'];

    public function unitBarang(): HasMany
    {
        return $this->hasMany(UnitBarang::class);
    }

    /** Alasan lokasi tidak boleh dihapus (null = boleh). */
    public function alasanTidakBisaDihapus(): ?string
    {
        return $this->unitBarang()->exists() ? 'masih ada unit barang di lokasi ini' : null;
    }
}
