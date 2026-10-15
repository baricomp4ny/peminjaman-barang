<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPeminjaman;
use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TolakRequest;
use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Ajuan yang menunggu verifikasi. Detail ajuan memakai admin.peminjaman.show. */
class AjuanController extends Controller
{
    public function index(): View
    {
        $ajuan = Peminjaman::with(['peminjam', 'detail.barang'])
            ->where('status', StatusPeminjaman::Menunggu)
            ->orderBy('tanggal_pinjam')   // yang paling dekat tanggal pinjamnya di atas
            ->orderBy('created_at')
            ->paginate(15);

        return view('admin.ajuan.index', compact('ajuan'));
    }

    public function setujui(Request $request, Peminjaman $peminjaman, PeminjamanService $service): RedirectResponse
    {
        try {
            $service->setujui($peminjaman, $request->user());
        } catch (PeminjamanException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.ajuan.index')
            ->with('success', "Ajuan {$peminjaman->kode_peminjaman} disetujui. Peminjam sudah diberi notifikasi.");
    }

    public function tolak(TolakRequest $request, Peminjaman $peminjaman, PeminjamanService $service): RedirectResponse
    {
        try {
            $service->tolak($peminjaman, $request->user(), $request->validated('alasan_penolakan'));
        } catch (PeminjamanException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.ajuan.index')
            ->with('success', "Ajuan {$peminjaman->kode_peminjaman} ditolak. Peminjam sudah diberi notifikasi.");
    }
}
