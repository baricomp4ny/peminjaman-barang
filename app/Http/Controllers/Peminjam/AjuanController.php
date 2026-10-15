<?php

namespace App\Http\Controllers\Peminjam;

use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Peminjam\AjuanRequest;
use App\Models\Barang;
use App\Models\Pengaturan;
use App\Services\PeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AjuanController extends Controller
{
    public function create(Request $request, PeminjamanService $service): View
    {
        return view('peminjam.ajuan.create', [
            'barang' => Barang::orderBy('nama')->get(['id', 'nama', 'kode']),
            'barangDipilih' => $request->integer('barang_id') ?: null,
            'terlambat' => $service->punyaPinjamanTerlambat($request->user()),
            'maksHari' => (int) Pengaturan::nilai(Pengaturan::LAMA_PINJAM_MAKS),
            'maksUnit' => (int) Pengaturan::nilai(Pengaturan::UNIT_MAKS_PER_AJUAN),
        ]);
    }

    public function store(AjuanRequest $request, PeminjamanService $service): RedirectResponse
    {
        try {
            $peminjaman = $service->ajukan($request->user(), $request->validated());
        } catch (PeminjamanException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('peminjam.pinjaman.show', $peminjaman)
            ->with('success', "Ajuan {$peminjaman->kode_peminjaman} berhasil dikirim. Tunggu verifikasi admin.");
    }
}
