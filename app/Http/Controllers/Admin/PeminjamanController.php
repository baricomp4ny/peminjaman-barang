<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SerahTerimaRequest;
use App\Models\Peminjaman;
use App\Models\UnitBarang;
use App\Services\DendaService;
use App\Services\KetersediaanService;
use App\Services\PeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Peminjaman yang sudah disetujui (siap diambil) dan yang sedang dipinjam. */
class PeminjamanController extends Controller
{
    public function index(Request $request, PeminjamanService $service): View
    {
        $tab = in_array($request->tab, ['disetujui', 'dipinjam', 'terlambat'], true) ? $request->tab : 'disetujui';

        $query = Peminjaman::with(['peminjam', 'detail.barang', 'peminjamanUnit']);
        match ($tab) {
            'disetujui' => $query->where('status', Peminjaman::STATUS_DISETUJUI)->orderBy('tanggal_pinjam'),
            'dipinjam' => $query->where('status', Peminjaman::STATUS_DIPINJAM)->orderBy('tanggal_rencana_kembali'),
            'terlambat' => $query->terlambat()->orderBy('tanggal_rencana_kembali'),
        };

        return view('admin.peminjaman.index', [
            'tab' => $tab,
            'peminjaman' => $query->paginate(15)->withQueryString(),
            'jumlah' => [
                'disetujui' => Peminjaman::where('status', Peminjaman::STATUS_DISETUJUI)->count(),
                'dipinjam' => Peminjaman::where('status', Peminjaman::STATUS_DIPINJAM)->count(),
                'terlambat' => Peminjaman::terlambat()->count(),
            ],
            'service' => $service,
        ]);
    }

    /** Detail semua status; tombol aksi menyesuaikan status. */
    public function show(Peminjaman $peminjaman, KetersediaanService $ketersediaan, PeminjamanService $service, DendaService $denda): View
    {
        $peminjaman->load(['peminjam', 'verifikator', 'detail.barang', 'peminjamanUnit.unitBarang.barang']);

        // Untuk ajuan menunggu: tampilkan sisa unit per barang pada rentang tanggalnya
        $stok = $peminjaman->status === Peminjaman::STATUS_MENUNGGU
            ? $ketersediaan->hitung($peminjaman->detail->pluck('barang_id')->all(), $peminjaman->tanggal_pinjam, $peminjaman->tanggal_rencana_kembali)
            : [];

        return view('admin.peminjaman.show', [
            'peminjaman' => $peminjaman,
            'stok' => $stok,
            'batasAmbil' => $service->batasAmbil($peminjaman),
            'punyaTerlambat' => $service->punyaPinjamanTerlambat($peminjaman->peminjam),
            'perkiraanDenda' => $peminjaman->isTerlambat() ? $denda->perkiraan($peminjaman) : 0,
        ]);
    }

    public function serahForm(Peminjaman $peminjaman, PeminjamanService $service): View|RedirectResponse
    {
        if ($peminjaman->status !== Peminjaman::STATUS_DISETUJUI) {
            return redirect()->route('admin.peminjaman.show', $peminjaman)
                ->with('error', 'Serah-terima hanya untuk ajuan berstatus disetujui.');
        }

        $peminjaman->load(['peminjam', 'detail.barang']);

        // Unit yang bisa dipilih: jenis sesuai ajuan, kondisi baik, status tersedia
        $unit = UnitBarang::with(['barang', 'lokasi'])
            ->whereIn('barang_id', $peminjaman->detail->pluck('barang_id'))
            ->where('kondisi', UnitBarang::KONDISI_BAIK)
            ->where('status', UnitBarang::STATUS_TERSEDIA)
            ->orderBy('kode_unit')
            ->get()
            ->groupBy('barang_id');

        $jendela = null;
        try {
            $service->cekJendelaSerah($peminjaman);
        } catch (PeminjamanException $e) {
            $jendela = $e->getMessage();
        }

        return view('admin.peminjaman.serah', compact('peminjaman', 'unit', 'jendela'));
    }

    public function serahkan(SerahTerimaRequest $request, Peminjaman $peminjaman, PeminjamanService $service): RedirectResponse
    {
        try {
            $service->serahkan($peminjaman, $request->user(), $request->validated('kode_unit'));
        } catch (PeminjamanException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.peminjaman.show', $peminjaman)
            ->with('success', "Serah-terima {$peminjaman->kode_peminjaman} berhasil. Status: dipinjam.");
    }
}
