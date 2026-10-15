<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPeminjaman;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RiwayatRequest;
use App\Models\Peminjaman;
use Illuminate\View\View;

/** Semua transaksi dengan filter status, kata kunci, dan rentang tanggal pinjam. */
class RiwayatController extends Controller
{
    public function index(RiwayatRequest $request): View
    {
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

        return view('admin.riwayat.index', [
            'riwayat' => $riwayat,
            'daftarStatus' => StatusPeminjaman::cases(),
        ]);
    }
}
