<?php

namespace App\Http\Controllers\Peminjam;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Kategori;
use App\Services\KetersediaanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        // Sisa unit untuk hari ini (satu query untuk semua barang di halaman)
        $stok = $ketersediaan->hitung($barang->pluck('id')->all(), today(), today());

        return view('peminjam.katalog.index', [
            'barang' => $barang,
            'stok' => $stok,
            'kategori' => Kategori::orderBy('nama')->get(),
        ]);
    }

    public function show(Barang $barang, KetersediaanService $ketersediaan): View
    {
        $barang->load('kategori');
        $stok = $ketersediaan->hitung([$barang->id], today(), today())[$barang->id];

        return view('peminjam.katalog.show', compact('barang', 'stok'));
    }
}
