<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PengembalianRequest;
use App\Models\Peminjaman;
use App\Services\DendaService;
use App\Services\PeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PengembalianController extends Controller
{
    /** Pinjaman yang masih berjalan; yang terlambat ditampilkan paling atas. */
    public function index(Request $request): View
    {
        $peminjaman = Peminjaman::with(['peminjam', 'detail.barang', 'peminjamanUnit'])
            ->where('status', Peminjaman::STATUS_DIPINJAM)
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('kode_peminjaman', 'like', '%'.$request->q.'%')
                    ->orWhereHas('peminjam', fn ($u) => $u->where('name', 'like', '%'.$request->q.'%'));
            }))
            ->orderBy('tanggal_rencana_kembali')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pengembalian.index', compact('peminjaman'));
    }

    public function create(Peminjaman $peminjaman, DendaService $denda): View|RedirectResponse
    {
        if ($peminjaman->status !== Peminjaman::STATUS_DIPINJAM) {
            return redirect()->route('admin.peminjaman.show', $peminjaman)
                ->with('error', 'Pengembalian hanya untuk peminjaman berstatus dipinjam.');
        }

        $peminjaman->load(['peminjam', 'detail.barang', 'peminjamanUnit.unitBarang.barang']);

        return view('admin.pengembalian.create', [
            'peminjaman' => $peminjaman,
            'belumKembali' => $peminjaman->peminjamanUnit->whereNull('dikembalikan_pada'),
            'dendaHariIni' => $denda->hitung($peminjaman->tanggal_rencana_kembali, today()),
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

        $denda = (int) $hasil->sum('denda');
        $pesan = "{$hasil->count()} unit dikembalikan."
            .($denda > 0 ? ' Denda: Rp'.number_format($denda, 0, ',', '.').'.' : ' Tanpa denda.')
            .($peminjaman->fresh()->status === Peminjaman::STATUS_SELESAI ? ' Peminjaman selesai.' : ' Masih ada unit yang belum kembali.');

        return redirect()->route('admin.peminjaman.show', $peminjaman)->with('success', $pesan);
    }
}
