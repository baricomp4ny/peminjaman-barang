<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPeminjaman;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RiwayatRequest;
use App\Models\Peminjaman;
use App\Services\LaporanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Semua transaksi dengan filter status, kata kunci, dan rentang tanggal pinjam; bisa diunduh PDF/Excel. */
class RiwayatController extends Controller
{
    public function index(RiwayatRequest $request, LaporanService $laporan): View|Response
    {
        // Unduh: isi sama persis dengan filter yang sedang dipakai (pengganti Laporan Transaksi)
        if ($request->filled('format')) {
            $data = $this->query($request)->with('detail.barang')->orderBy('tanggal_pinjam')->get();

            return $laporan->unduh($laporan->riwayat($data, $this->periode($request)), $request->format);
        }

        return view('admin.riwayat.index', [
            'riwayat' => $this->query($request)->with(['detail.barang', 'peminjamanUnit'])->latest()->paginate(20)->withQueryString(),
            'daftarStatus' => StatusPeminjaman::cases(),
        ]);
    }

    private function query(RiwayatRequest $request): Builder
    {
        return Peminjaman::with('peminjam')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('kode_peminjaman', 'like', '%'.$request->q.'%')
                    ->orWhereHas('peminjam', fn ($u) => $u->where('name', 'like', '%'.$request->q.'%')
                        ->orWhere('nomor_identitas', 'like', '%'.$request->q.'%'));
            }))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('tanggal_pinjam', '>=', $request->dari))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('tanggal_pinjam', '<=', $request->sampai));
    }

    private function periode(RiwayatRequest $request): string
    {
        $dari = $request->filled('dari') ? Carbon::parse($request->dari)->translatedFormat('d F Y') : 'awal';
        $sampai = $request->filled('sampai') ? Carbon::parse($request->sampai)->translatedFormat('d F Y') : 'sekarang';
        $status = $request->filled('status') ? ' · status '.StatusPeminjaman::from($request->status)->label() : '';
        $cari = $request->filled('q') ? ' · cari "'.$request->q.'"' : '';

        return "Tanggal pinjam {$dari} s/d {$sampai}{$status}{$cari}";
    }
}
