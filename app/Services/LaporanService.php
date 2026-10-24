<?php

namespace App\Services;

use App\Enums\KondisiUnit;
use App\Exports\LaporanExport;
use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use App\Support\WhatsApp;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyusun data laporan dalam bentuk netral (judul, kolom, baris, ringkasan)
 * supaya tampilan web, PDF, dan Excel memakai isi yang sama persis.
 * Kunci "web" hanya untuk halaman: kartu angka, kolom utama per baris, dan tautan aksi per baris.
 */
class LaporanService
{
    public const JENIS = [
        'pengembalian' => 'Laporan Pengembalian',
        'inventaris' => 'Laporan Inventaris Barang',
        'keterlambatan' => 'Laporan Keterlambatan',
        'statistik' => 'Laporan Statistik Pemakaian',
    ];

    public function __construct(private KeterlambatanService $keterlambatan) {}

    /**
     * @return array{jenis:string, judul:string, periode:string, kolom:array<string>, baris:array<array>, ringkasan:array<string, string|int>, web:array}
     */
    public function buat(string $jenis, Carbon $dari, Carbon $sampai): array
    {
        $dari = $dari->copy()->startOfDay();
        $sampai = $sampai->copy()->endOfDay();

        $data = match ($jenis) {
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

    /**
     * Laporan riwayat transaksi dari hasil filter halaman Riwayat (status, kata kunci, rentang tanggal).
     *
     * @param  Collection<int, Peminjaman>  $data
     */
    public function riwayat(Collection $data, string $periode): array
    {
        return [
            'jenis' => 'riwayat',
            'judul' => 'Laporan Riwayat Transaksi',
            'periode' => $periode,
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

    /** Unduh laporan sebagai PDF (A4 landscape) atau Excel; dipakai halaman Laporan & Riwayat. */
    public function unduh(array $laporan, string $format): Response
    {
        $namaFile = 'laporan-'.$laporan['jenis'].'-'.now()->format('Ymd-His');

        return $format === 'excel'
            ? Excel::download(new LaporanExport($laporan), $namaFile.'.xlsx')
            : Pdf::loadView('admin.laporan.pdf', ['laporan' => $laporan])->setPaper('a4', 'landscape')->download($namaFile.'.pdf');
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
                'Kondisi baik' => $data->where('kondisi_kembali', KondisiUnit::Baik)->count(),
                'Kondisi rusak' => $data->where('kondisi_kembali', KondisiUnit::Rusak)->count(),
                'Hilang' => $data->where('kondisi_kembali', KondisiUnit::Hilang)->count(),
                'Kembali terlambat' => $data->where('hari_terlambat', '>', 0)->count().' unit',
            ],
            'web' => $this->web(
                kartu: [
                    ['Unit dikembalikan', $data->count(), false],
                    ['Kondisi baik', $data->where('kondisi_kembali', KondisiUnit::Baik)->count(), false],
                    ['Rusak / hilang', $data->whereIn('kondisi_kembali', [KondisiUnit::Rusak, KondisiUnit::Hilang])->count(), true],
                    ['Kembali terlambat', $data->where('hari_terlambat', '>', 0)->count(), true],
                ],
                utama: [1 => '1.2fr', 2 => '1.6fr', 3 => '1fr', 6 => '1.2fr', 7 => '1fr'],
                pil: [7],
                aksi: $data->values()->map(fn ($u) => [
                    ['Buka transaksi', route('admin.peminjaman.show', $u->peminjaman_id)],
                ])->all(),
            ),
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
            'web' => $this->web(
                kartu: [
                    ['Total unit', $data->count(), false],
                    ['Kondisi baik', $data->where('kondisi', KondisiUnit::Baik)->count(), false],
                    ['Kondisi rusak', $data->where('kondisi', KondisiUnit::Rusak)->count(), true],
                    ['Kondisi hilang', $data->where('kondisi', KondisiUnit::Hilang)->count(), true],
                ],
                utama: [1 => '1fr', 2 => '1.8fr', 4 => '1.5fr', 5 => '1fr', 7 => '1fr'],
                pil: [5, 7],
                aksi: $data->values()->map(fn ($u) => [
                    [$u->status->value === 'perbaikan' ? 'Buka unit & perbaikan' : 'Buka unit', route('admin.unit.edit', $u)],
                ])->all(),
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

        $masihTerlambat = PeminjamanUnit::with(['peminjaman.peminjam', 'peminjaman.detail.barang', 'unitBarang.barang'])
            ->whereNull('dikembalikan_pada')
            ->whereHas('peminjaman', fn ($q) => $q->terlambat())
            ->get();

        $semua = $kembaliTerlambat->concat($masihTerlambat)->values();
        $baris = $semua->map(function ($u, $i) {
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
            'web' => $this->web(
                kartu: [
                    ['Kembali terlambat', $kembaliTerlambat->count(), false],
                    ['Masih terlambat', $masihTerlambat->count(), true],
                    ['Peminjam terlibat', $semua->pluck('peminjaman.user_id')->unique()->count(), false],
                    ['Terlambat terlama (hari)', (int) collect($baris)->map(fn ($b) => (int) $b[8])->max(), true],
                ],
                utama: [1 => '1.3fr', 2 => '1.5fr', 5 => '1.5fr', 6 => '1fr', 8 => '1fr'],
                pil: [8],
                // Peminjaman yang masih terlambat bisa langsung diingatkan lewat WhatsApp
                aksi: $semua->map(fn ($u) => array_values(array_filter([
                    ['Buka transaksi', route('admin.peminjaman.show', $u->peminjaman_id)],
                    $u->dikembalikan_pada === null && ($wa = WhatsApp::tautan($u->peminjaman->peminjam->no_hp, WhatsApp::pesanTerlambat($u->peminjaman)))
                        ? ['WhatsApp', $wa, 'wa'] : null,
                ])))->all(),
            ),
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
            'web' => $this->web(
                kartu: [
                    ['Total transaksi', $pakai->sum('transaksi'), false],
                    ['Unit dipinjam', $pakai->sum('unit'), false],
                    ['Jenis alat dipakai', $data->where('unit', '>', 0)->count(), false],
                    ['Tidak pernah dipinjam', $data->where('unit', 0)->count(), true],
                ],
                utama: [0 => '0.7fr', 2 => '1.8fr', 3 => '1.6fr', 4 => '1fr', 5 => '1fr'],
                pil: [6],
                aksi: $data->map(fn ($r) => [
                    ['Lihat jadwal alat', route('admin.jadwal', ['barang_id' => $r['barang']->id])],
                ])->all(),
            ),
        ];
    }

    /**
     * Pengaturan tampilan web.
     *
     * @param  array<int, array{0:string, 1:int, 2:bool}>  $kartu  [label, angka, perlu perhatian?]
     * @param  array<int, string>  $utama  indeks kolom yang tampil di baris => lebar kolom grid
     * @param  array<int, int>  $pil  indeks kolom yang ditampilkan sebagai pil berwarna
     * @param  array<int, array>  $aksi  per baris: [[label, url, jenis?], ...]
     */
    private function web(array $kartu, array $utama, array $pil, array $aksi): array
    {
        return compact('kartu', 'utama', 'pil', 'aksi');
    }
}
