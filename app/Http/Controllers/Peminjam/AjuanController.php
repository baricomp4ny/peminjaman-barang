<?php

namespace App\Http\Controllers\Peminjam;

use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Peminjam\AjuanRequest;
use App\Models\Barang;
use App\Models\Pengaturan;
use App\Services\IdentitasPeminjamService;
use App\Services\PeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Form ajuan publik: peminjam tidak perlu login. */
class AjuanController extends Controller
{
    public function create(Request $request): View
    {
        return view('peminjam.ajuan.create', [
            'barang' => Barang::orderBy('nama')->get(['id', 'nama', 'kode']),
            'barangDipilih' => $request->query('barang_id'),
            'maksHari' => Pengaturan::angka(Pengaturan::LAMA_PINJAM_MAKS),
            'maksUnit' => Pengaturan::angka(Pengaturan::UNIT_MAKS_PER_AJUAN),
        ]);
    }

    public function store(AjuanRequest $request, IdentitasPeminjamService $identitas, PeminjamanService $service): RedirectResponse
    {
        try {
            $peminjam = $identitas->cariAtauBuat($request->identitas());
            $peminjaman = $service->ajukan($peminjam, $request->ajuan());
        } catch (PeminjamanException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Pengaju langsung boleh melihat halaman status ajuannya di sesi ini
        StatusController::beriAkses($request, $peminjaman);

        return redirect()->route('peminjam.status.show', $peminjaman->kode_peminjaman)
            ->with('success', "Ajuan {$peminjaman->kode_peminjaman} berhasil dikirim. Simpan kode ini; rincian juga dikirim ke {$peminjam->email}.");
    }
}
