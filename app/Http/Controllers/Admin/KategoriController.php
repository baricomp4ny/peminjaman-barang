<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KategoriRequest;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(Request $request): View
    {
        $kategori = Kategori::query()
            ->withCount('barang')
            ->when($request->filled('q'), fn ($q) => $q->where('nama', 'like', '%'.$request->q.'%'))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.kategori.index', compact('kategori'));
    }

    public function create(): View
    {
        return view('admin.kategori.form', ['kategori' => new Kategori()]);
    }

    public function store(KategoriRequest $request): RedirectResponse
    {
        $kategori = Kategori::create($request->validated());

        return redirect()->route('admin.kategori.index')
            ->with('success', "Kategori \"{$kategori->nama}\" berhasil ditambahkan.");
    }

    public function edit(Kategori $kategori): View
    {
        return view('admin.kategori.form', compact('kategori'));
    }

    public function update(KategoriRequest $request, Kategori $kategori): RedirectResponse
    {
        $kategori->update($request->validated());

        return redirect()->route('admin.kategori.index')
            ->with('success', "Kategori \"{$kategori->nama}\" berhasil diperbarui.");
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        // Kategori yang masih dipakai jenis barang tidak boleh dihapus
        if ($kategori->barang()->exists()) {
            return back()->with('error', "Kategori \"{$kategori->nama}\" tidak bisa dihapus karena masih dipakai oleh jenis barang.");
        }

        $kategori->delete();

        return redirect()->route('admin.kategori.index')
            ->with('success', "Kategori \"{$kategori->nama}\" berhasil dihapus.");
    }
}
