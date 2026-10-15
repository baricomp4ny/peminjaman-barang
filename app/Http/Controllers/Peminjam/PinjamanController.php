<?php

namespace App\Http\Controllers\Peminjam;

use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Services\DendaService;
use App\Services\PeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PinjamanController extends Controller
{
    public function index(Request $request): View
    {
        $pinjaman = $request->user()->peminjaman()
            ->with('detail.barang')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('peminjam.pinjaman.index', compact('pinjaman'));
    }

    public function show(Peminjaman $peminjaman, PeminjamanService $service, DendaService $denda): View
    {
        $this->authorize('view', $peminjaman);

        $peminjaman->load(['detail.barang', 'peminjamanUnit.unitBarang.barang', 'verifikator']);

        return view('peminjam.pinjaman.show', [
            'peminjaman' => $peminjaman,
            'batasAmbil' => $service->batasAmbil($peminjaman),
            'perkiraanDenda' => $peminjaman->isTerlambat() ? $denda->perkiraan($peminjaman) : 0,
        ]);
    }

    public function batal(Request $request, Peminjaman $peminjaman, PeminjamanService $service): RedirectResponse
    {
        $this->authorize('batalkan', $peminjaman);

        try {
            $service->batalkan($peminjaman, $request->user());
        } catch (PeminjamanException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('peminjam.pinjaman.show', $peminjaman)
            ->with('success', "Ajuan {$peminjaman->kode_peminjaman} berhasil dibatalkan.");
    }
}
