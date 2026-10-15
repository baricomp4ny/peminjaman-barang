<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UnitBarangStoreRequest;
use App\Http\Requests\Admin\UnitBarangUpdateRequest;
use App\Models\Barang;
use App\Models\Lokasi;
use App\Models\UnitBarang;
use App\Services\KodeUnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitBarangController extends Controller
{
    public function index(Request $request): View
    {
        $unit = UnitBarang::query()
            ->with(['barang', 'lokasi'])
            ->when($request->filled('q'), fn ($q) => $q->where('kode_unit', 'like', '%'.$request->q.'%'))
            ->when($request->filled('barang_id'), fn ($q) => $q->where('barang_id', $request->barang_id))
            ->when($request->filled('lokasi_id'), fn ($q) => $q->where('lokasi_id', $request->lokasi_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('kode_unit')
            ->paginate(15)
            ->withQueryString();

        return view('admin.unit.index', [
            'unit' => $unit,
            'barang' => Barang::orderBy('nama')->get(['id', 'nama', 'kode']),
            'lokasi' => Lokasi::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function create(Request $request, KodeUnitService $kodeUnit): View
    {
        $barang = Barang::orderBy('nama')->get(['id', 'nama', 'kode']);

        // Pratinjau kode berikutnya untuk tiap jenis barang (ditampilkan di form)
        $kodeBerikutnya = $barang->mapWithKeys(fn ($b) => [
            $b->id => $kodeUnit->format($b->kode, $kodeUnit->nomorTerakhir($b->kode) + 1),
        ]);

        return view('admin.unit.create', [
            'barang' => $barang,
            'lokasi' => Lokasi::orderBy('nama')->get(['id', 'nama']),
            'kodeBerikutnya' => $kodeBerikutnya,
            'barangDipilih' => $request->query('barang_id'),
        ]);
    }

    public function store(UnitBarangStoreRequest $request, KodeUnitService $kodeUnit): RedirectResponse
    {
        $barang = Barang::findOrFail($request->barang_id);
        $unitBaru = $kodeUnit->buatUnit($barang, $request->validated(), $request->integer('jumlah'));

        $rentang = $unitBaru->count() === 1
            ? $unitBaru->first()->kode_unit
            : $unitBaru->first()->kode_unit.' s/d '.$unitBaru->last()->kode_unit;

        return redirect()->route('admin.barang.show', $barang)
            ->with('success', "{$unitBaru->count()} unit {$barang->nama} berhasil ditambahkan ({$rentang}).");
    }

    public function edit(UnitBarang $unit): View
    {
        $unit->load('barang');

        return view('admin.unit.edit', [
            'unit' => $unit,
            'lokasi' => Lokasi::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(UnitBarangUpdateRequest $request, UnitBarang $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return redirect()->route('admin.barang.show', $unit->barang_id)
            ->with('success', "Unit {$unit->kode_unit} berhasil diperbarui.");
    }

    public function destroy(UnitBarang $unit): RedirectResponse
    {
        // Unit yang pernah dipinjam tetap disimpan demi riwayat; ubah statusnya saja
        if ($unit->peminjamanUnit()->exists()) {
            return back()->with('error', "Unit {$unit->kode_unit} tidak bisa dihapus karena tercatat di riwayat peminjaman. Ubah statusnya bila tidak dipakai lagi.");
        }

        $unit->delete();

        return back()->with('success', "Unit {$unit->kode_unit} berhasil dihapus.");
    }
}
