<?php

namespace App\Http\Controllers\Peminjam;

use App\Enums\StatusPeminjaman;
use App\Http\Controllers\Controller;
use App\Http\Requests\Peminjam\KatalogRequest;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Pengaturan;
use App\Services\KetersediaanService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/** Beranda publik = katalog alat (tanpa login). */
class KatalogController extends Controller
{
    private const PER_HALAMAN = 9;

    public function index(KatalogRequest $request, KetersediaanService $ketersediaan): View
    {
        $mulai = $request->mulai();
        $selesai = $request->selesai();

        $cocok = Barang::query()
            ->with('kategori')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('nama', 'like', '%'.$request->q.'%')
                ->orWhere('kode', 'like', '%'.$request->q.'%')))
            ->when($request->filled('kategori_id'), fn ($q) => $q->where('kategori_id', $request->kategori_id))
            ->when($request->filled('lokasi_id'), fn ($q) => $q->whereHas('unit', fn ($u) => $u->where('lokasi_id', $request->lokasi_id)))
            ->orderBy('nama')
            ->get();

        // Sisa unit pada rentang tanggal yang dipilih (default hari ini)
        $stok = $ketersediaan->hitung($cocok->pluck('id')->all(), $mulai, $selesai);

        // Filter "hanya tersedia" butuh angka sisa, jadi dikerjakan di koleksi.
        // Jumlah jenis alat lab kecil (puluhan), sehingga aman tanpa paginasi di database.
        $cocok = $cocok
            ->when($request->boolean('tersedia'), fn ($c) => $c->filter(fn ($b) => ($stok[$b->id]['sisa'] ?? 0) > 0))
            ->values();

        $halaman = LengthAwarePaginator::resolveCurrentPage();
        $barang = (new LengthAwarePaginator($cocok->forPage($halaman, self::PER_HALAMAN), $cocok->count(), self::PER_HALAMAN, $halaman, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]))->fragment('katalog');

        // Statistik hero selalu untuk hari ini & semua alat
        $stokHariIni = $ketersediaan->hitung(Barang::pluck('id')->all(), today(), today());

        return view('peminjam.katalog.index', [
            'barang' => $barang,
            'stok' => $stok,
            'rentang' => ['mulai' => $mulai, 'selesai' => $selesai, 'dipilih' => $request->filled('mulai')],
            'kategori' => Kategori::withCount('barang')->orderBy('nama')->get(),
            'lokasi' => Lokasi::orderBy('nama')->get(),
            // Alat yang paling sering dipinjam (jumlah unit dari pinjaman yang sudah diambil)
            'populer' => Barang::query()
                ->withSum(['peminjamanDetail as total_dipinjam' => fn ($q) => $q->whereHas('peminjaman', fn ($p) => $p->whereIn('status', [StatusPeminjaman::Dipinjam, StatusPeminjaman::Selesai]))], 'jumlah')
                ->having('total_dipinjam', '>', 0)
                ->orderByDesc('total_dipinjam')
                ->limit(3)
                ->get(),
            'statistik' => [
                'jenis' => count($stokHariIni),
                'tersedia' => collect($stokHariIni)->sum('sisa'),
            ],
            'aturan' => [
                'lama' => Pengaturan::angka(Pengaturan::LAMA_PINJAM_MAKS),
                'unit' => Pengaturan::angka(Pengaturan::UNIT_MAKS_PER_AJUAN),
                'penangguhan' => Pengaturan::angka(Pengaturan::LAMA_PENANGGUHAN_HARI),
            ],
            'adaFoto' => file_exists(public_path('images/hero-lab.jpg')),
            'adaFilter' => $request->hasAny(['q', 'kategori_id', 'lokasi_id', 'mulai', 'tersedia']),
        ]);
    }

    public function show(Barang $barang, KetersediaanService $ketersediaan): View
    {
        $barang->load(['kategori', 'unit.lokasi']);
        $stok = $ketersediaan->hitung([$barang->id], today(), today())[$barang->id];

        // Alat lain di kategori yang sama
        $lainnya = Barang::with('kategori')
            ->where('kategori_id', $barang->kategori_id)
            ->whereKeyNot($barang->id)
            ->orderBy('nama')
            ->limit(4)
            ->get();

        return view('peminjam.katalog.show', [
            'barang' => $barang,
            'stok' => $stok,
            'lainnya' => $lainnya,
            'stokLain' => $ketersediaan->hitung($lainnya->pluck('id')->all(), today(), today()),
            'lokasi' => $barang->unit->pluck('lokasi.nama')->unique()->values(),
            'maksHari' => Pengaturan::angka(Pengaturan::LAMA_PINJAM_MAKS),
        ]);
    }
}
