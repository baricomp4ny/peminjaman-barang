<?php

namespace App\Http\Controllers\Peminjam;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Pinjaman yang masih berjalan: menunggu, disetujui, dipinjam
        $aktif = $user->peminjaman()
            ->with(['detail.barang', 'peminjamanUnit'])
            ->whereIn('status', [Peminjaman::STATUS_MENUNGGU, Peminjaman::STATUS_DISETUJUI, Peminjaman::STATUS_DIPINJAM])
            ->orderBy('tanggal_pinjam')
            ->get();

        return view('peminjam.beranda', [
            'aktif' => $aktif,
            'terlambat' => $aktif->filter(fn ($p) => $p->isTerlambat()),
            'notifikasi' => $user->unreadNotifications()->limit(5)->get(),
            'jumlahNotif' => $user->unreadNotifications()->count(),
            'jumlahSelesai' => $user->peminjaman()->where('status', Peminjaman::STATUS_SELESAI)->count(),
        ]);
    }
}
