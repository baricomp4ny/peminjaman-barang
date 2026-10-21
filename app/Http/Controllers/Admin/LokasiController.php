<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HapusBanyak;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LokasiRequest;
use App\Models\Lokasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LokasiController extends Controller
{
    use HapusBanyak;

    public function index(Request $request): View
    {
        $lokasi = Lokasi::query()
            ->withCount('unitBarang')
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('nama', 'like', '%'.$request->q.'%')
                    ->orWhere('keterangan', 'like', '%'.$request->q.'%');
            }))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.lokasi.index', compact('lokasi'));
    }

    public function create(): View
    {
        return view('admin.lokasi.form', ['lokasi' => new Lokasi]);
    }

    public function store(LokasiRequest $request): RedirectResponse
    {
        $lokasi = Lokasi::create($request->validated());

        return redirect()->route('admin.lokasi.index')
            ->with('success', "Lokasi \"{$lokasi->nama}\" berhasil ditambahkan.");
    }

    public function edit(Lokasi $lokasi): View
    {
        return view('admin.lokasi.form', compact('lokasi'));
    }

    public function update(LokasiRequest $request, Lokasi $lokasi): RedirectResponse
    {
        $lokasi->update($request->validated());

        return redirect()->route('admin.lokasi.index')
            ->with('success', "Lokasi \"{$lokasi->nama}\" berhasil diperbarui.");
    }

    public function destroy(Lokasi $lokasi): RedirectResponse
    {
        // Lokasi yang masih menyimpan unit tidak boleh dihapus
        if ($alasan = $lokasi->alasanTidakBisaDihapus()) {
            return back()->with('error', "Lokasi \"{$lokasi->nama}\" tidak bisa dihapus karena {$alasan}.");
        }

        $lokasi->delete();

        return redirect()->route('admin.lokasi.index')
            ->with('success', "Lokasi \"{$lokasi->nama}\" berhasil dihapus.");
    }

    public function hapusTerpilih(Request $request): RedirectResponse
    {
        return $this->hapusBanyak($request, Lokasi::query(), 'lokasi', fn ($l) => $l->nama, fn ($l) => $l->alasanTidakBisaDihapus());
    }
}
