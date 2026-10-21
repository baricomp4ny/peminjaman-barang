<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPeminjaman;
use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PengembalianRequest;
use App\Models\Peminjaman;
use App\Services\KeterlambatanService;
use App\Services\PeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PengembalianController extends Controller
{
    public function create(Peminjaman $peminjaman, KeterlambatanService $keterlambatan): View|RedirectResponse
    {
        if ($peminjaman->status !== StatusPeminjaman::Dipinjam) {
            return redirect()->route('admin.peminjaman.show', $peminjaman)
                ->with('error', 'Pengembalian hanya untuk peminjaman berstatus dipinjam.');
        }

        $peminjaman->load(['peminjam', 'detail.barang', 'peminjamanUnit.unitBarang.barang']);

        return view('admin.pengembalian.create', [
            'peminjaman' => $peminjaman,
            'belumKembali' => $peminjaman->peminjamanUnit->whereNull('dikembalikan_pada'),
            'terlambatHariIni' => $keterlambatan->hariTerlambat($peminjaman->tanggal_rencana_kembali, today()),
        ]);
    }

    public function store(PengembalianRequest $request, Peminjaman $peminjaman, PeminjamanService $service): RedirectResponse
    {
        $unit = $request->unitDipilih();
        if ($unit === []) {
            return back()->withInput()->with('error', 'Centang minimal satu unit yang dikembalikan.');
        }

        // Jam diambil dari waktu sekarang; tanggal boleh mundur bila pencatatan terlambat
        $waktu = Carbon::parse($request->validated('tanggal_kembali'))->setTimeFrom(now());

        try {
            $hasil = $service->kembalikan($peminjaman, $request->user(), $unit, $waktu);
        } catch (PeminjamanException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $terlambat = (int) $hasil->max('hari_terlambat');
        $pesan = "{$hasil->count()} unit dikembalikan."
            .($terlambat > 0 ? " Terlambat {$terlambat} hari." : ' Tepat waktu.')
            .($peminjaman->fresh()->status === StatusPeminjaman::Selesai ? ' Peminjaman selesai.' : ' Masih ada unit yang belum kembali.');

        $pesan = "{$peminjaman->kode_peminjaman}: {$pesan}";

        return $request->boolean('dari_scan')
            ? redirect()->route('admin.scan.index', ['mode' => 'kembali'])->with('success', $pesan)
            : redirect()->route('admin.peminjaman.show', $peminjaman)->with('success', $pesan);
    }
}
