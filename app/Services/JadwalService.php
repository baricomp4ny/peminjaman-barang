<?php

namespace App\Services;

use App\Enums\StatusPeminjaman;
use App\Enums\StatusUnit;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Data halaman Jadwal Pemakaian Alat (admin & publik).
 *  - perUnit  : timeline per unit. Unit yang sudah diserahkan memakai data asli; ajuan disetujui
 *               (dan menunggu, khusus admin) yang belum diambil "ditempatkan" ke unit kosong
 *               pertama dari jenisnya, hanya untuk tampilan.
 *  - perAlat  : terpakai / kapasitas per jenis alat per hari (KetersediaanService).
 *  - ringkasan: metrik minggu dari tanggal mulai.
 * Mode publik tidak menampilkan nama peminjam, kode peminjaman, maupun ajuan menunggu.
 */
class JadwalService
{
    public const HARI = 14;

    public function __construct(private KetersediaanService $ketersediaan) {}

    /** @param  array{kategori_id?:?string, lokasi_id?:?string, barang_id?:?string, q?:?string}  $filter */
    public function susun(Carbon $mulai, array $filter, bool $publik): array
    {
        $mulai = $mulai->copy()->startOfDay();
        $akhir = $mulai->copy()->addDays(self::HARI - 1);
        $tanggal = collect(range(0, self::HARI - 1))->map(fn ($i) => $mulai->copy()->addDays($i));

        $unit = UnitBarang::with(['barang.kategori', 'lokasi'])
            ->when($filter['kategori_id'] ?? null, fn ($q, $id) => $q->whereHas('barang', fn ($b) => $b->where('kategori_id', $id)))
            ->when($filter['lokasi_id'] ?? null, fn ($q, $id) => $q->where('lokasi_id', $id))
            // Dari tombol "Lihat Alat Ini dalam Matriks" di detail alat
            ->when($filter['barang_id'] ?? null, fn ($q, $id) => $q->where('barang_id', $id))
            ->when($filter['q'] ?? null, fn ($q, $cari) => $q->where(fn ($w) => $w
                ->where('kode_unit', 'like', "%{$cari}%")
                ->orWhereHas('barang', fn ($b) => $b->where('nama', 'like', "%{$cari}%")->orWhere('kode', 'like', "%{$cari}%"))))
            ->orderBy('kode_unit')
            ->get();

        return [
            'tanggal' => $tanggal,
            'perUnit' => $this->perUnit($unit, $mulai, $akhir, $publik),
            'perAlat' => $this->perAlat($unit, $tanggal),
            'ringkasan' => $this->ringkasan($mulai),
            'jumlahUnit' => $unit->count(),
        ];
    }

    private function perUnit(Collection $unit, Carbon $mulai, Carbon $akhir, bool $publik): array
    {
        $baris = $unit->mapWithKeys(fn (UnitBarang $u) => [$u->id => [
            'unit' => $u,
            // Unit perbaikan/hilang tidak bisa dipinjam di tanggal mana pun
            'tidakTersedia' => in_array($u->status, [StatusUnit::Perbaikan, StatusUnit::Hilang], true),
            'blok' => [],
        ]])->all();

        // 1) Unit yang benar-benar sudah diserahkan (sedang dipinjam atau sudah kembali)
        PeminjamanUnit::with('peminjaman.peminjam')
            ->whereIn('unit_barang_id', array_keys($baris))
            ->whereHas('peminjaman', fn ($q) => $q->whereNotNull('diserahkan_pada')->whereDate('diserahkan_pada', '<=', $akhir->toDateString()))
            ->where(fn ($q) => $q->whereNull('dikembalikan_pada')->orWhereDate('dikembalikan_pada', '>=', $mulai->toDateString()))
            ->get()
            ->each(function (PeminjamanUnit $pu) use (&$baris, $publik) {
                $p = $pu->peminjaman;
                $belumKembali = $pu->dikembalikan_pada === null;
                $terlambat = $belumKembali && $p->tanggal_rencana_kembali->lt(today());
                $dari = $p->diserahkan_pada->copy()->startOfDay();
                // Belum kembali & terlambat: tetap dipegang peminjam sampai hari ini
                $sampai = $belumKembali ? $p->tanggal_rencana_kembali->copy()->max(today()) : $pu->dikembalikan_pada->copy()->startOfDay();

                $baris[$pu->unit_barang_id]['blok'][] = $this->blok($p, $dari, $sampai, $terlambat ? 'terlambat' : 'dipinjam', $publik);
            });

        // 2) Ajuan yang belum diambil: tempatkan ke unit kosong pertama dari jenisnya
        $status = $publik ? [StatusPeminjaman::Disetujui] : [StatusPeminjaman::Disetujui, StatusPeminjaman::Menunggu];
        $perJenis = collect($baris)->reject(fn ($b) => $b['tidakTersedia'])->groupBy(fn ($b) => $b['unit']->barang_id, true)->map->keys();

        Peminjaman::with(['detail', 'peminjam'])
            ->whereIn('status', $status)
            ->whereDate('tanggal_pinjam', '<=', $akhir->toDateString())
            ->whereDate('tanggal_rencana_kembali', '>=', $mulai->toDateString())
            ->orderBy('tanggal_pinjam')
            ->get()
            ->sortBy(fn ($p) => $p->status === StatusPeminjaman::Disetujui ? 0 : 1) // disetujui didahulukan
            ->each(function (Peminjaman $p) use (&$baris, $perJenis, $publik) {
                $dari = $p->tanggal_pinjam->copy()->startOfDay();
                $sampai = $p->tanggal_rencana_kembali->copy()->startOfDay();
                $jenis = $p->status === StatusPeminjaman::Disetujui ? 'disetujui' : 'menunggu';

                foreach ($p->detail as $d) {
                    for ($n = 0; $n < $d->jumlah; $n++) {
                        $kosong = collect($perJenis[$d->barang_id] ?? [])->first(fn ($id) => ! $this->bentrok($baris[$id]['blok'], $dari, $sampai));
                        if ($kosong) {
                            $baris[$kosong]['blok'][] = $this->blok($p, $dari, $sampai, $jenis, $publik);
                        }
                    }
                }
            });

        // Ubah tanggal menjadi indeks kolom (dipotong ke jendela 14 hari)
        foreach ($baris as &$b) {
            $b['blok'] = collect($b['blok'])->map(function ($blok) use ($mulai) {
                $blok['kolomAwal'] = max(0, (int) $mulai->diffInDays($blok['dari'], false));
                $blok['kolomAkhir'] = min(self::HARI - 1, (int) $mulai->diffInDays($blok['sampai'], false));

                return $blok;
            })->filter(fn ($blok) => $blok['kolomAwal'] <= $blok['kolomAkhir'])->sortBy('kolomAwal')->values()->all();
        }

        return array_values($baris);
    }

    private function bentrok(array $blok, Carbon $dari, Carbon $sampai): bool
    {
        return collect($blok)->contains(fn ($b) => $b['dari']->lte($sampai) && $b['sampai']->gte($dari));
    }

    private function blok(Peminjaman $p, Carbon $dari, Carbon $sampai, string $jenis, bool $publik): array
    {
        $label = ['dipinjam' => 'Dipinjam', 'terlambat' => 'Terlambat', 'disetujui' => 'Dipesan', 'menunggu' => 'Menunggu verifikasi'][$jenis];
        $rentang = $dari->translatedFormat('d M').' – '.$sampai->translatedFormat('d M');

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'jenis' => $jenis,
            // Publik: tanpa identitas peminjam & kode peminjaman
            'judul' => $publik ? $label : $p->kode_peminjaman.' · '.$p->peminjam->name,
            'sub' => $publik ? $rentang : $label.' · '.$rentang,
            'url' => $publik ? null : route('admin.peminjaman.show', $p),
        ];
    }

    /** @return array<int, array{barang:\App\Models\Barang, hari:array<int, array{terpakai:int, kapasitas:int}>}> */
    private function perAlat(Collection $unit, Collection $tanggal): array
    {
        $barang = $unit->pluck('barang')->unique('id')->sortBy('nama')->values();
        $ids = $barang->pluck('id')->all();
        $perHari = $tanggal->map(fn ($t) => $ids ? $this->ketersediaan->hitung($ids, $t, $t) : []);

        return $barang->map(fn ($b) => [
            'barang' => $b,
            'hari' => $perHari->map(fn ($stok) => [
                'terpakai' => $stok[$b->id]['terpakai'] ?? 0,
                'kapasitas' => $stok[$b->id]['kapasitas'] ?? 0,
            ])->all(),
        ])->all();
    }

    private function ringkasan(Carbon $mulai): array
    {
        $awal = $mulai->copy()->startOfWeek();
        $akhir = $mulai->copy()->endOfWeek();

        $populer = PeminjamanUnit::query()
            ->join('unit_barang', 'unit_barang.id', '=', 'peminjaman_unit.unit_barang_id')
            ->join('barang', 'barang.id', '=', 'unit_barang.barang_id')
            ->join('peminjaman', 'peminjaman.id', '=', 'peminjaman_unit.peminjaman_id')
            ->whereBetween('peminjaman.diserahkan_pada', [$awal, $akhir])
            ->selectRaw('barang.nama, COUNT(*) as total')
            ->groupBy('barang.nama')
            ->orderByDesc('total')
            ->limit(3)
            ->pluck('total', 'nama');

        // Tingkat pemakaian = total terpakai / total kapasitas selama 7 hari
        $ids = UnitBarang::distinct()->pluck('barang_id')->all();
        $terpakai = $kapasitas = 0;
        for ($t = $awal->copy(); $t->lte($akhir); $t->addDay()) {
            foreach ($ids ? $this->ketersediaan->hitung($ids, $t, $t) : [] as $s) {
                $terpakai += $s['terpakai'];
                $kapasitas += $s['kapasitas'];
            }
        }

        return [
            'periode' => $awal->translatedFormat('d M').' – '.$akhir->translatedFormat('d M Y'),
            'ajuan' => Peminjaman::whereBetween('created_at', [$awal, $akhir])->count(),
            'diserahkan' => PeminjamanUnit::whereHas('peminjaman', fn ($q) => $q->whereBetween('diserahkan_pada', [$awal, $akhir]))->count(),
            'kembaliTerlambat' => PeminjamanUnit::whereBetween('dikembalikan_pada', [$awal, $akhir])->where('hari_terlambat', '>', 0)->count(),
            'sedangTerlambat' => Peminjaman::terlambat()->count(),
            'pemakaian' => $kapasitas > 0 ? (int) round($terpakai / $kapasitas * 100) : 0,
            'populer' => $populer->all(),
        ];
    }
}
