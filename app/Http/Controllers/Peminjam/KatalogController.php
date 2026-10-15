<?php

namespace App\Http\Controllers\Peminjam;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Pengaturan;
use App\Services\KetersediaanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Beranda publik = katalog alat (tanpa login). */
class KatalogController extends Controller
{
    public function index(Request $request, KetersediaanService $ketersediaan): View
    {
        $barang = Barang::query()
            ->with('kategori')
            ->when($request->filled('q'), fn ($q) => $q->where('nama', 'like', '%'.$request->q.'%'))
            ->when($request->filled('kategori_id'), fn ($q) => $q->where('kategori_id', $request->kategori_id))
            ->orderBy('nama')
            ->paginate(12)
            ->withQueryString();

        // Sisa unit hari ini untuk SEMUA jenis alat (dipakai di kartu & statistik hero)
        $stokSemua = $ketersediaan->hitung(Barang::pluck('id')->all(), today(), today());

        return view('peminjam.katalog.index', [
            'barang' => $barang,
            'stok' => $stokSemua,
            // Kategori + beberapa nama alat untuk kartu "bento"
            'kategori' => Kategori::with(['barang' => fn ($q) => $q->orderBy('nama')->select('id', 'kategori_id', 'nama')])
                ->withCount('barang')
                ->orderBy('nama')
                ->get(),
            'statistik' => [
                'jenis' => count($stokSemua),
                'tersedia' => collect($stokSemua)->sum('sisa'),
                'unit' => collect($stokSemua)->sum('kapasitas'),
            ],
            'aturan' => [
                'lama' => (int) Pengaturan::nilai(Pengaturan::LAMA_PINJAM_MAKS),
                'unit' => (int) Pengaturan::nilai(Pengaturan::UNIT_MAKS_PER_AJUAN),
                'denda' => (int) Pengaturan::nilai(Pengaturan::DENDA_PER_HARI),
            ],
            'adaFoto' => file_exists(public_path('images/hero-lab.jpg')),
            'sedangCari' => $request->hasAny(['q', 'kategori_id']),
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
            'maksHari' => (int) Pengaturan::nilai(Pengaturan::LAMA_PINJAM_MAKS),
        ]);
    }
}
