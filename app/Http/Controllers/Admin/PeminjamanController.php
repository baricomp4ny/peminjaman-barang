<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KondisiUnit;
use App\Enums\StatusPeminjaman;
use App\Enums\StatusUnit;
use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SerahTerimaRequest;
use App\Models\Peminjaman;
use App\Models\UnitBarang;
use App\Models\User;
use App\Services\BuktiPeminjamanService;
use App\Services\KeterlambatanService;
use App\Services\KetersediaanService;
use App\Services\PeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Peminjaman yang sudah disetujui (siap diambil) dan yang sedang dipinjam. */
class PeminjamanController extends Controller
{
    public function index(Request $request, PeminjamanService $service): View
    {
        $tab = in_array($request->tab, ['disetujui', 'dipinjam', 'terlambat'], true) ? $request->tab : 'disetujui';

        $query = Peminjaman::with(['peminjam', 'detail.barang', 'peminjamanUnit']);
        match ($tab) {
            'disetujui' => $query->where('status', StatusPeminjaman::Disetujui)->orderBy('tanggal_pinjam'),
            'dipinjam' => $query->where('status', StatusPeminjaman::Dipinjam)->orderBy('tanggal_rencana_kembali'),
            'terlambat' => $query->terlambat()->orderBy('tanggal_rencana_kembali'),
        };

        return view('admin.peminjaman.index', [
            'tab' => $tab,
            'peminjaman' => $query->paginate(15)->withQueryString(),
            'jumlah' => [
                'disetujui' => Peminjaman::where('status', StatusPeminjaman::Disetujui)->count(),
                'dipinjam' => Peminjaman::where('status', StatusPeminjaman::Dipinjam)->count(),
                'terlambat' => Peminjaman::terlambat()->count(),
            ],
            'service' => $service,
        ]);
    }

    /** Detail semua status; tombol aksi menyesuaikan status. */
    public function show(Peminjaman $peminjaman, KetersediaanService $ketersediaan, PeminjamanService $service, KeterlambatanService $keterlambatan): View
    {
        $peminjaman->load(['peminjam', 'verifikator', 'detail.barang', 'peminjamanUnit.unitBarang.barang']);

        // Untuk ajuan menunggu: tampilkan sisa unit per barang pada rentang tanggalnya
        $stok = $peminjaman->status === StatusPeminjaman::Menunggu
            ? $ketersediaan->hitung($peminjaman->detail->pluck('barang_id')->all(), $peminjaman->tanggal_pinjam, $peminjaman->tanggal_rencana_kembali)
            : [];

        return view('admin.peminjaman.show', [
            'peminjaman' => $peminjaman,
            'stok' => $stok,
            'batasAmbil' => $service->batasAmbil($peminjaman),
            'punyaTerlambat' => $service->punyaPinjamanTerlambat($peminjaman->peminjam),
            // Panel verifikasi: riwayat keterlambatan & penangguhan peminjam
            'jumlahTerlambat' => User::whereKey($peminjaman->user_id)->withJumlahTerlambat()->value('jumlah_terlambat') ?? 0,
            'penangguhan' => $keterlambatan->pesanPenangguhan($peminjaman->peminjam, untukAdmin: true),
        ]);
    }

    /** Unduh PDF bukti peminjaman (kop lab, QR, identitas, barang, tanda tangan). */
    public function bukti(Peminjaman $peminjaman, BuktiPeminjamanService $bukti): Response
    {
        return response($bukti->pdf($peminjaman), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$bukti->namaFile($peminjaman).'"',
        ]);
    }

    /** Buka surat permohonan yang diunggah peminjam (disk privat, khusus admin). */
    public function surat(Peminjaman $peminjaman): StreamedResponse
    {
        abort_unless($peminjaman->surat_peminjaman && Storage::disk('local')->exists($peminjaman->surat_peminjaman), 404, 'Surat peminjaman tidak ditemukan.');

        $nama = 'surat-'.$peminjaman->kode_peminjaman.'.'.pathinfo($peminjaman->surat_peminjaman, PATHINFO_EXTENSION);

        return Storage::disk('local')->response($peminjaman->surat_peminjaman, $nama);
    }

    public function serahForm(Peminjaman $peminjaman, PeminjamanService $service): View|RedirectResponse
    {
        if ($peminjaman->status !== StatusPeminjaman::Disetujui) {
            return redirect()->route('admin.peminjaman.show', $peminjaman)
                ->with('error', 'Serah-terima hanya untuk ajuan berstatus disetujui.');
        }

        $peminjaman->load(['peminjam', 'detail.barang']);

        // Unit yang bisa dipilih: jenis sesuai ajuan, kondisi baik, status tersedia
        $unit = UnitBarang::with(['barang', 'lokasi'])
            ->whereIn('barang_id', $peminjaman->detail->pluck('barang_id'))
            ->where('kondisi', KondisiUnit::Baik)
            ->where('status', StatusUnit::Tersedia)
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

        $pesan = "Serah-terima {$peminjaman->kode_peminjaman} berhasil. Status: dipinjam.";

        // Dari halaman scan: kembali ke sana agar bisa langsung melayani peminjam berikutnya
        return $request->boolean('dari_scan')
            ? redirect()->route('admin.scan.index', ['mode' => 'serah'])->with('success', $pesan)
            : redirect()->route('admin.peminjaman.show', $peminjaman)->with('success', $pesan);
    }
}
