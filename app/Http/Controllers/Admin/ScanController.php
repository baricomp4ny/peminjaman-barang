<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScanCariRequest;
use App\Http\Requests\Admin\ScanCekUnitRequest;
use App\Models\Peminjaman;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(Request $request): View
    {
        $mode = $request->input('mode') === ScanService::MODE_KEMBALI ? ScanService::MODE_KEMBALI : ScanService::MODE_SERAH;

        return view('admin.scan.index', [
            'mode' => $mode,
            // Bisa dibuka langsung dari halaman detail: ?mode=serah&peminjaman=ID
            'awal' => $request->filled('peminjaman') ? Peminjaman::find($request->integer('peminjaman'))?->kode_peminjaman : null,
        ]);
    }

    /** Langkah 1: kenali kode (PJM / kartu / unit) dan kembalikan transaksi yang cocok. */
    public function cari(ScanCariRequest $request, ScanService $scan): JsonResponse
    {
        try {
            $daftar = $scan->cari($request->kode, $request->mode);
        } catch (PeminjamanException $e) {
            return response()->json(['ok' => false, 'pesan' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'transaksi' => $daftar->map(fn ($p) => $scan->ringkasan($p, $request->mode))->values(),
        ]);
    }

    /** Langkah 2: validasi satu unit hasil scan terhadap transaksi yang dibuka. */
    public function cekUnit(ScanCekUnitRequest $request, ScanService $scan): JsonResponse
    {
        $p = Peminjaman::findOrFail($request->peminjaman_id);

        try {
            $unit = $scan->cekUnit($p, $request->kode, $request->mode, $request->input('sudah', []));
        } catch (PeminjamanException $e) {
            return response()->json(['ok' => false, 'pesan' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'unit' => [
                'unit_id' => $unit->id,
                'kode_unit' => $unit->kode_unit,
                'barang_id' => $unit->barang_id,
                'nama' => $unit->barang->nama,
            ],
        ]);
    }
}
