<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPeminjaman;
use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $statusUnit = UnitBarang::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.dashboard', [
            'kartu' => [
                'menunggu' => Peminjaman::where('status', StatusPeminjaman::Menunggu)->count(),
                'siapAmbil' => Peminjaman::where('status', StatusPeminjaman::Disetujui)->count(),
                'dipinjam' => Peminjaman::where('status', StatusPeminjaman::Dipinjam)->count(),
                'terlambat' => Peminjaman::terlambat()->count(),
                // Unit yang dikembalikan terlambat bulan ini (pengganti kartu denda)
                'kembaliTerlambatBulanIni' => PeminjamanUnit::whereBetween('dikembalikan_pada', [now()->startOfMonth(), now()])->where('hari_terlambat', '>', 0)->count(),
            ],
            'statusUnit' => $statusUnit,
            'totalUnit' => $statusUnit->sum(),
            'ajuanTerbaru' => Peminjaman::with(['peminjam', 'detail.barang'])
                ->where('status', StatusPeminjaman::Menunggu)->oldest()->limit(5)->get(),
            'terlambat' => Peminjaman::with(['peminjam', 'detail.barang'])
                ->terlambat()->orderBy('tanggal_rencana_kembali')->limit(5)->get(),
            'jatuhTempo' => Peminjaman::with(['peminjam', 'detail.barang'])
                ->where('status', StatusPeminjaman::Dipinjam)
                ->whereBetween('tanggal_rencana_kembali', [today()->toDateString(), today()->addDay()->toDateString()])
                ->orderBy('tanggal_rencana_kembali')->get(),
            'grafik' => $this->grafik(),
        ]);
    }

    /** Data grafik 6 bulan terakhir, berdasarkan tanggal serah-terima (alat benar-benar dipinjam). */
    private function grafik(): array
    {
        $mulai = now()->startOfMonth()->subMonths(5);

        // Peminjaman per bulan: dikelompokkan di PHP supaya tidak bergantung fungsi tanggal MySQL/SQLite
        $perBulan = Peminjaman::whereNotNull('diserahkan_pada')->where('diserahkan_pada', '>=', $mulai)
            ->pluck('diserahkan_pada')
            ->countBy(fn ($t) => Carbon::parse($t)->format('Y-m'));
        $bulan = collect(range(0, 5))->map(fn ($i) => $mulai->copy()->addMonths($i));

        // 5 alat terpopuler: jumlah unit yang diserahkan
        $populer = DB::table('peminjaman_unit')
            ->join('peminjaman', 'peminjaman.id', '=', 'peminjaman_unit.peminjaman_id')
            ->join('unit_barang', 'unit_barang.id', '=', 'peminjaman_unit.unit_barang_id')
            ->join('barang', 'barang.id', '=', 'unit_barang.barang_id')
            ->where('peminjaman.diserahkan_pada', '>=', $mulai)
            ->groupBy('barang.id', 'barang.nama')
            ->selectRaw('barang.nama, COUNT(*) as unit')
            ->orderByDesc('unit')->orderBy('barang.nama')
            ->limit(5)->get();

        return [
            'periode' => $bulan->first()->translatedFormat('M Y').' – '.$bulan->last()->translatedFormat('M Y'),
            'bulan' => [
                'label' => $bulan->map(fn ($b) => $b->translatedFormat('M Y'))->all(),
                'nilai' => $bulan->map(fn ($b) => $perBulan[$b->format('Y-m')] ?? 0)->all(),
            ],
            'populer' => [
                'label' => $populer->pluck('nama')->all(),
                'nilai' => $populer->pluck('unit')->map(fn ($n) => (int) $n)->all(),
            ],
        ];
    }
}
