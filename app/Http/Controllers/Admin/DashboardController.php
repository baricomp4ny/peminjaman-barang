<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $statusUnit = UnitBarang::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.dashboard', [
            'kartu' => [
                'menunggu' => Peminjaman::where('status', Peminjaman::STATUS_MENUNGGU)->count(),
                'siapAmbil' => Peminjaman::where('status', Peminjaman::STATUS_DISETUJUI)->count(),
                'dipinjam' => Peminjaman::where('status', Peminjaman::STATUS_DIPINJAM)->count(),
                'terlambat' => Peminjaman::terlambat()->count(),
                'dendaBulanIni' => (int) PeminjamanUnit::whereBetween('dikembalikan_pada', [now()->startOfMonth(), now()])->sum('denda'),
            ],
            'statusUnit' => $statusUnit,
            'totalUnit' => $statusUnit->sum(),
            'ajuanTerbaru' => Peminjaman::with(['peminjam', 'detail.barang'])
                ->where('status', Peminjaman::STATUS_MENUNGGU)->oldest()->limit(5)->get(),
            'terlambat' => Peminjaman::with(['peminjam', 'detail.barang'])
                ->terlambat()->orderBy('tanggal_rencana_kembali')->limit(5)->get(),
            'jatuhTempo' => Peminjaman::with(['peminjam', 'detail.barang'])
                ->where('status', Peminjaman::STATUS_DIPINJAM)
                ->whereBetween('tanggal_rencana_kembali', [today()->toDateString(), today()->addDay()->toDateString()])
                ->orderBy('tanggal_rencana_kembali')->get(),
        ]);
    }
}
