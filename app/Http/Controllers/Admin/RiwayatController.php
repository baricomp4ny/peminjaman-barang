<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Semua transaksi dengan filter status, kata kunci, dan rentang tanggal pinjam. */
class RiwayatController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'status' => ['nullable', 'in:'.implode(',', Peminjaman::STATUS)],
        ]);

        $riwayat = Peminjaman::with(['peminjam', 'detail.barang', 'peminjamanUnit'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('kode_peminjaman', 'like', '%'.$request->q.'%')
                    ->orWhereHas('peminjam', fn ($u) => $u->where('name', 'like', '%'.$request->q.'%')
                        ->orWhere('nomor_identitas', 'like', '%'.$request->q.'%'));
            }))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('tanggal_pinjam', '>=', $request->dari))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('tanggal_pinjam', '<=', $request->sampai))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.riwayat.index', compact('riwayat'));
    }
}
