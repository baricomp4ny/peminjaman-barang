<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Concerns\HapusBanyak;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PerbaikanSelesaiRequest;
use App\Http\Requests\Admin\UnitBarangStoreRequest;
use App\Http\Requests\Admin\UnitBarangUpdateRequest;
use App\Models\Barang;
use App\Models\Lokasi;
use App\Models\UnitBarang;
use App\Services\KodeUnitService;
use App\Services\KondisiUnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UnitBarangController extends Controller
{
    use HapusBanyak;

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
        $unit->load(['barang', 'riwayatKondisi.pencatat', 'riwayatKondisi.peminjaman']);

        return view('admin.unit.edit', [
            'unit' => $unit,
            'lokasi' => Lokasi::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(UnitBarangUpdateRequest $request, UnitBarang $unit, KondisiUnitService $kondisiUnit): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($unit, $data, $request, $kondisiUnit) {
            $unit->update(collect($data)->only(['lokasi_id', 'tanggal_perolehan'])->all());
            // Perubahan kondisi/status lewat service supaya tercatat di riwayat
            $kondisiUnit->catat($unit, KondisiUnit::from($data['kondisi']), StatusUnit::from($data['status']),
                'ubah_manual', $request->user(), $data['keterangan'] ?? null);
        });

        return redirect()->route('admin.barang.show', $unit->barang_id)
            ->with('success', "Unit {$unit->kode_unit} berhasil diperbarui.");
    }

    /** Tombol "Perbaikan selesai": unit rusak kembali baik & tersedia. */
    public function perbaikanSelesai(PerbaikanSelesaiRequest $request, UnitBarang $unit, KondisiUnitService $kondisiUnit): RedirectResponse
    {
        try {
            $kondisiUnit->perbaikanSelesai($unit, $request->user(), $request->validated('keterangan'));
        } catch (PeminjamanException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.unit.edit', $unit)
            ->with('success', "Perbaikan unit {$unit->kode_unit} selesai. Unit kembali tersedia untuk dipinjam.");
    }

    public function destroy(UnitBarang $unit): RedirectResponse
    {
        // Unit yang pernah dipinjam tetap disimpan demi riwayat; ubah statusnya saja
        if ($alasan = $unit->alasanTidakBisaDihapus()) {
            return back()->with('error', "Unit {$unit->kode_unit} tidak bisa dihapus karena {$alasan}. Ubah statusnya bila tidak dipakai lagi.");
        }

        $unit->delete();

        return back()->with('success', "Unit {$unit->kode_unit} berhasil dihapus.");
    }

    public function hapusTerpilih(Request $request): RedirectResponse
    {
        return $this->hapusBanyak($request, UnitBarang::query(), 'unit', fn ($u) => $u->kode_unit, fn ($u) => $u->alasanTidakBisaDihapus());
    }
}
