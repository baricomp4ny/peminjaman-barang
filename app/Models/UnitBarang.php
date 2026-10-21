<?php

namespace App\Models;

use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Satu unit fisik alat; kode_unit adalah isi QR-nya. */
class UnitBarang extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'unit_barang';

    protected $fillable = ['barang_id', 'lokasi_id', 'kode_unit', 'kondisi', 'status', 'tanggal_perolehan'];

    protected $casts = [
        'kondisi' => KondisiUnit::class,
        'status' => StatusUnit::class,
        'tanggal_perolehan' => 'date',
    ];

    /** Nilai bawaan unit baru. */
    protected $attributes = [
        'kondisi' => 'baik',
        'status' => 'tersedia',
    ];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function peminjamanUnit(): HasMany
    {
        return $this->hasMany(PeminjamanUnit::class);
    }

    /** Riwayat perubahan kondisi/status, terbaru di atas. */
    public function riwayatKondisi(): HasMany
    {
        return $this->hasMany(RiwayatKondisiUnit::class)->latest();
    }

    /** Catatan pengembalian terakhir (sumber keterangan kondisi di laporan inventaris). */
    public function pengembalianTerakhir(): HasOne
    {
        return $this->hasOne(PeminjamanUnit::class)->ofMany(['dikembalikan_pada' => 'max'], fn ($q) => $q->whereNotNull('dikembalikan_pada'));
    }

    /** Keterangan kondisi yang mudah dibaca, contoh "Rusak — lensa retak (kembali 02/10/2026)". */
    public function keteranganKondisi(): string
    {
        $kembali = $this->pengembalianTerakhir;
        $dariPengembalian = $kembali && $kembali->kondisi_kembali === $this->kondisi && $this->kondisi !== KondisiUnit::Baik;

        $dasar = match (true) {
            $this->kondisi === KondisiUnit::Rusak => 'Rusak, dalam perbaikan',
            $this->kondisi === KondisiUnit::Hilang => 'Hilang',
            $this->status === StatusUnit::Dipinjam => 'Baik, sedang dipinjam',
            default => 'Baik, siap dipinjam',
        };

        if (! $dariPengembalian) {
            return $dasar;
        }

        return $dasar.' — '.($kembali->catatan ?: 'dilaporkan saat pengembalian').' (kembali '.$kembali->dikembalikan_pada->format('d/m/Y').')';
    }

    /** Unit boleh dipinjam hanya jika kondisinya baik dan statusnya tersedia. */
    public function bisaDipinjam(): bool
    {
        return $this->kondisi === KondisiUnit::Baik && $this->status === StatusUnit::Tersedia;
    }

    /** Unit yang pernah dipinjam disimpan demi riwayat (null = boleh dihapus). */
    public function alasanTidakBisaDihapus(): ?string
    {
        return $this->peminjamanUnit()->exists() ? 'tercatat di riwayat peminjaman' : null;
    }
}
