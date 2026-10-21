<?php

namespace App\Models;

use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu catatan perubahan kondisi/status unit (dibuat lewat KondisiUnitService). */
class RiwayatKondisiUnit extends Model
{
    use HasUlids;

    public const JENIS = [
        'pengembalian' => 'Pengembalian',
        'ubah_manual' => 'Diubah admin',
        'perbaikan_selesai' => 'Perbaikan selesai',
    ];

    protected $table = 'riwayat_kondisi_unit';

    protected $fillable = [
        'unit_barang_id', 'jenis', 'kondisi_lama', 'kondisi_baru', 'status_lama', 'status_baru',
        'keterangan', 'dicatat_oleh', 'peminjaman_id',
    ];

    protected $casts = [
        'kondisi_lama' => KondisiUnit::class,
        'kondisi_baru' => KondisiUnit::class,
        'status_lama' => StatusUnit::class,
        'status_baru' => StatusUnit::class,
    ];

    public function unitBarang(): BelongsTo
    {
        return $this->belongsTo(UnitBarang::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function labelJenis(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }
}
