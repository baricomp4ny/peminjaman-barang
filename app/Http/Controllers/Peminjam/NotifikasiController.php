<?php

namespace App\Http\Controllers\Peminjam;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $notifikasi = $request->user()->notifications()->paginate(15);

        return view('peminjam.notifikasi.index', compact('notifikasi'));
    }

    /** Tandai dibaca lalu buka halaman tujuan notifikasi. */
    public function buka(Request $request, string $id): RedirectResponse
    {
        // Hanya notifikasi milik user sendiri (selain itu 404)
        $notif = $request->user()->notifications()->findOrFail($id);
        $notif->markAsRead();

        return redirect($notif->data['url'] ?? route('peminjam.notifikasi.index'));
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
