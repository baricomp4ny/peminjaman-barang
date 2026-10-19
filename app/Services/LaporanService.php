<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use Illuminate\Support\Carbon;

/**
 * Menyusun data laporan dalam bentuk netral (judul, kolom, baris, ringkasan)
 * supaya tampilan web, PDF, dan Excel memakai isi yang sama persis.
 */
class LaporanService
{
    public const JENIS = [
        'peminjaman' => 'Laporan Peminjaman',
        'pengembalian' => 'Laporan Pengembalian',
        'inventaris' => 'Laporan Inventaris Barang',
        'keterlambatan' => 'Laporan Keterlambatan',
        'statistik' => 'Laporan Statistik Pemakaian',
    ];

    public function __construct(private KeterlambatanService $keterlambatan) {}

    /**
     * @return array{jenis:string, judul:string, periode:string, kolom:array<string>, baris:array<array>, ringkasan:array<string, string|int>}
     */
    public function buat(string $jenis, Carbon $dari, Carbon $sampai): array
    {
        $dari = $dari->copy()->startOfDay();
        $sampai = $sampai->copy()->endOfDay();

        $data = match ($jenis) {
            'peminjaman' => $this->peminjaman($dari, $sampai),
            'pengembalian' => $this->pengembalian($dari, $sampai),
            'inventaris' => $this->inventaris(),
            'keterlambatan' => $this->keterlambatan($dari, $sampai),
            'statistik' => $this->statistik($dari, $sampai),
        };

        return array_merge([
            'jenis' => $jenis,
            'judul' => self::JENIS[$jenis],
            'periode' => $jenis === 'inventaris'
                ? 'Per '.now()->translatedFormat('d F Y H:i')
                : $dari->translatedFormat('d F Y').' s/d '.$sampai->translatedFormat('d F Y'),
        ], $data);
    }

    private function peminjaman(Carbon $dari, Carbon $sampai): array
    {
        $data = Peminjaman::with(['peminjam', 'detail.barang'])
            ->whereBetween('tanggal_pinjam', [$dari->toDateString(), $sampai->toDateString()])
            ->orderBy('tanggal_pinjam')
            ->get();

        return [
            'kolom' => ['No', 'Kode', 'Peminjam', 'No. Identitas', 'Barang', 'Tgl Pinjam', 'Rencana Kembali', 'Status'],
            'baris' => $data->values()->map(fn ($p, $i) => [
                $i + 1, $p->kode_peminjaman, $p->peminjam->name, $p->peminjam->nomor_identitas, $p->ringkasanBarang(),
                $p->tanggal_pinjam->format('d/m/Y'), $p->tanggal_rencana_kembali->format('d/m/Y'), $p->status->label(),
            ])->all(),
            'ringkasan' => array_merge(
                ['Total transaksi' => $data->count()],
                $data->countBy(fn ($r) => $r->status->value)->mapWithKeys(fn ($n, $s) => ['Status '.$s => $n])->all()
            ),
        ];
    }

    private function pengembalian(Carbon $dari, Carbon $sampai): array
    {
        $data = PeminjamanUnit::with(['peminjaman.peminjam', 'unitBarang.barang'])
            ->whereBetween('dikembalikan_pada', [$dari, $sampai])
            ->orderBy('dikembalikan_pada')
            ->get();

        return [
            'kolom' => ['No', 'Kode Peminjaman', 'Peminjam', 'Kode Unit', 'Barang', 'Diserahkan', 'Dikembalikan', 'Kondisi', 'Terlambat (hari)', 'Catatan'],
            'baris' => $data->values()->map(fn ($u, $i) => [
                $i + 1, $u->peminjaman->kode_peminjaman, $u->peminjaman->peminjam->name, $u->unitBarang->kode_unit,
                $u->unitBarang->barang->nama, $u->peminjaman->diserahkan_pada->format('d/m/Y'), $u->dikembalikan_pada->format('d/m/Y H:i'),
                $u->kondisi_kembali->label(), $u->hari_terlambat, $u->catatan ?? '-',
            ])->all(),
            'ringkasan' => [
                'Unit dikembalikan' => $data->count(),
                'Kondisi baik' => $data->where('kondisi_kembali', 'baik')->count(),
                'Kondisi rusak' => $data->where('kondisi_kembali', 'rusak')->count(),
                'Hilang' => $data->where('kondisi_kembali', 'hilang')->count(),
                'Kembali terlambat' => $data->where('hari_terlambat', '>', 0)->count().' unit',
            ],
        ];
    }

    private function inventaris(): array
    {
        $data = UnitBarang::with(['barang.kategori', 'lokasi', 'pengembalianTerakhir'])->orderBy('kode_unit')->get();

        // Rekap per tahun pembelian (tanggal_perolehan), unit tanpa tanggal masuk "Tanpa tahun"
        $perTahun = $data->groupBy(fn ($u) => $u->tanggal_perolehan?->format('Y') ?? 'Tanpa tahun')
            ->sortKeys()
            ->mapWithKeys(fn ($unit, $tahun) => ['Tahun '.$tahun => $unit->count().' unit ('.$unit->countBy(fn ($u) => $u->kondisi->label())->map(fn ($n, $k) => $n.' '.strtolower($k))->join(', ').')']);

        return [
            'kolom' => ['No', 'Kode Unit', 'Barang', 'Kategori', 'Lokasi', 'Kondisi', 'Keterangan Kondisi', 'Status', 'Tahun Pembelian', 'Tgl Perolehan'],
            'baris' => $data->values()->map(fn ($u, $i) => [
                $i + 1, $u->kode_unit, $u->barang->nama, $u->barang->kategori->nama, $u->lokasi->nama,
                $u->kondisi->label(), $u->keteranganKondisi(), $u->status->label(),
                $u->tanggal_perolehan?->format('Y') ?? '-', $u->tanggal_perolehan?->format('d/m/Y') ?? '-',
            ])->all(),
            'ringkasan' => array_merge(
                ['Total unit' => $data->count()],
                $data->countBy(fn ($r) => $r->kondisi->label())->mapWithKeys(fn ($n, $k) => ['Kondisi '.strtolower($k) => $n])->all(),
                $data->countBy(fn ($r) => $r->status->value)->mapWithKeys(fn ($n, $s) => ['Status '.$s => $n])->all(),
                $perTahun->all(),
            ),
        ];
    }

    /** Unit yang kembali terlambat pada periode + unit yang SAAT INI masih terlambat. */
    private function keterlambatan(Carbon $dari, Carbon $sampai): array
    {
        $kembaliTerlambat = PeminjamanUnit::with(['peminjaman.peminjam', 'unitBarang.barang'])
            ->where('hari_terlambat', '>', 0)
            ->whereBetween('dikembalikan_pada', [$dari, $sampai])
            ->get();

        $masihTerlambat = PeminjamanUnit::with(['peminjaman.peminjam', 'unitBarang.barang'])
            ->whereNull('dikembalikan_pada')
            ->whereHas('peminjaman', fn ($q) => $q->terlambat())
            ->get();

        $baris = $kembaliTerlambat->concat($masihTerlambat)->values()->map(function ($u, $i) {
            $p = $u->peminjaman;
            $belum = $u->dikembalikan_pada === null;
            $hari = $belum ? $this->keterlambatan->hariTerlambat($p->tanggal_rencana_kembali, today()) : $u->hari_terlambat;

            return [
                $i + 1, $p->kode_peminjaman, $p->peminjam->name, $p->peminjam->no_hp ?? '-', $u->unitBarang->kode_unit, $u->unitBarang->barang->nama,
                $p->tanggal_rencana_kembali->format('d/m/Y'), $belum ? 'Belum kembali' : $u->dikembalikan_pada->format('d/m/Y'),
                $hari.($belum ? ' (berjalan)' : ''),
            ];
        })->all();

        return [
            'kolom' => ['No', 'Kode Peminjaman', 'Peminjam', 'No. HP', 'Kode Unit', 'Barang', 'Rencana Kembali', 'Dikembalikan', 'Terlambat (hari)'],
            'baris' => $baris,
            'ringkasan' => [
                'Kembali terlambat (periode ini)' => $kembaliTerlambat->count().' unit',
                'Masih terlambat (hari ini)' => $masihTerlambat->count().' unit',
            ],
        ];
    }

    /**
     * Statistik pemakaian per jenis alat pada rentang tanggal serah-terima:
     * jumlah transaksi & jumlah unit dipinjam, diurutkan dari paling sering.
     * Alat yang tidak pernah dipinjam tetap tampil (paling jarang).
     */
    private function statistik(Carbon $dari, Carbon $sampai): array
    {
        $pakai = PeminjamanUnit::query()
            ->join('unit_barang', 'unit_barang.id', '=', 'peminjaman_unit.unit_barang_id')
            ->join('peminjaman', 'peminjaman.id', '=', 'peminjaman_unit.peminjaman_id')
            ->whereBetween('peminjaman.diserahkan_pada', [$dari, $sampai])
            ->selectRaw('unit_barang.barang_id, COUNT(*) as unit, COUNT(DISTINCT peminjaman_unit.peminjaman_id) as transaksi')
            ->groupBy('unit_barang.barang_id')
            ->get()
            ->keyBy('barang_id');

        $data = Barang::with('kategori')->get()
            ->map(fn ($b) => [
                'barang' => $b,
                'transaksi' => (int) ($pakai[$b->id]->transaksi ?? 0),
                'unit' => (int) ($pakai[$b->id]->unit ?? 0),
            ])
            ->sortBy([['unit', 'desc'], ['transaksi', 'desc'], [fn ($a, $b) => strcmp($a['barang']->nama, $b['barang']->nama)]])
            ->values();

        $nama = fn ($baris) => $baris->map(fn ($r) => $r['barang']->nama.' ('.$r['unit'].' unit)')->join(', ') ?: '-';

        return [
            'kolom' => ['Peringkat', 'Kode', 'Barang', 'Kategori', 'Jumlah Transaksi', 'Unit Dipinjam', 'Keterangan'],
            'baris' => $data->map(fn ($r, $i) => [
                $i + 1, $r['barang']->kode, $r['barang']->nama, $r['barang']->kategori->nama, $r['transaksi'], $r['unit'],
                match (true) {
                    $r['unit'] === 0 => 'Tidak pernah dipinjam',
                    $i < 3 => 'Paling sering',
                    $i >= $data->count() - 3 => 'Paling jarang',
                    default => '-',
                },
            ])->all(),
            'ringkasan' => [
                'Total transaksi' => $pakai->sum('transaksi'),
                'Total unit dipinjam' => $pakai->sum('unit'),
                'Paling sering dipinjam' => $nama($data->take(3)),
                'Paling jarang dipinjam' => $nama($data->reverse()->take(3)),
            ],
        ];
    }
}
