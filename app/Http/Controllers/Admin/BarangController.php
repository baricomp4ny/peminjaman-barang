<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BarangRequest;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\UnitBarang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BarangController extends Controller
{
    public function index(Request $request): View
    {
        $barang = Barang::query()
            ->with('kategori')
            ->withCount([
                'unit',
                'unit as unit_tersedia_count' => fn ($q) => $q->where('status', UnitBarang::STATUS_TERSEDIA),
            ])
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('nama', 'like', '%'.$request->q.'%')
                    ->orWhere('kode', 'like', '%'.$request->q.'%');
            }))
            ->when($request->filled('kategori_id'), fn ($q) => $q->where('kategori_id', $request->kategori_id))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        $kategori = Kategori::orderBy('nama')->get();

        return view('admin.barang.index', compact('barang', 'kategori'));
    }

    public function create(): View
    {
        return view('admin.barang.form', [
            'barang' => new Barang(),
            'kategori' => Kategori::orderBy('nama')->get(),
        ]);
    }

    public function store(BarangRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['foto', 'hapus_foto']);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('barang', 'public');
        }

        $barang = Barang::create($data);

        return redirect()->route('admin.barang.show', $barang)
            ->with('success', "Jenis barang \"{$barang->nama}\" berhasil ditambahkan. Silakan tambahkan unitnya.");
    }

    public function show(Barang $barang): View
    {
        $barang->load('kategori');

        $unit = $barang->unit()->with('lokasi')->orderBy('kode_unit')->get();

        return view('admin.barang.show', compact('barang', 'unit'));
    }

    public function edit(Barang $barang): View
    {
        return view('admin.barang.form', [
            'barang' => $barang,
            'kategori' => Kategori::orderBy('nama')->get(),
        ]);
    }

    public function update(BarangRequest $request, Barang $barang): RedirectResponse
    {
        $data = $request->safe()->except(['foto', 'hapus_foto']);

        // Ganti foto: hapus file lama supaya storage tidak menumpuk
        if ($request->hasFile('foto')) {
            $this->hapusFoto($barang);
            $data['foto'] = $request->file('foto')->store('barang', 'public');
        } elseif ($request->boolean('hapus_foto')) {
            $this->hapusFoto($barang);
            $data['foto'] = null;
        }

        $barang->update($data);

        return redirect()->route('admin.barang.show', $barang)
            ->with('success', "Jenis barang \"{$barang->nama}\" berhasil diperbarui.");
    }

    public function destroy(Barang $barang): RedirectResponse
    {
        // Jenis barang yang sudah punya unit atau pernah diajukan tidak boleh dihapus
        if ($barang->unit()->exists()) {
            return back()->with('error', "Jenis barang \"{$barang->nama}\" tidak bisa dihapus karena masih memiliki unit.");
        }

        if ($barang->peminjamanDetail()->exists()) {
            return back()->with('error', "Jenis barang \"{$barang->nama}\" tidak bisa dihapus karena tercatat di riwayat peminjaman.");
        }

        $this->hapusFoto($barang);
        $barang->delete();

        return redirect()->route('admin.barang.index')
            ->with('success', "Jenis barang \"{$barang->nama}\" berhasil dihapus.");
    }

    private function hapusFoto(Barang $barang): void
    {
        if ($barang->foto) {
            Storage::disk('public')->delete($barang->foto);
        }
    }
}
