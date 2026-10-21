<?php

namespace App\Http\Controllers\Peminjam;

use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Peminjam\AjuanRequest;
use App\Models\Barang;
use App\Models\Pengaturan;
use App\Services\IdentitasPeminjamService;
use App\Services\PeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Form ajuan publik: peminjam tidak perlu login. */
class AjuanController extends Controller
{
    public function create(Request $request): View
    {
        $tanggalAwal = $this->tanggalAwal($request->query('tanggal_pinjam'));

        return view('peminjam.ajuan.create', [
            'barang' => Barang::orderBy('nama')->get(['id', 'nama', 'kode']),
            'barangDipilih' => $request->query('barang_id'),
            // Dari halaman Jadwal: tanggal kosong yang diklik (tidak boleh sebelum hari ini)
            'tanggalAwal' => $tanggalAwal,
            'tanggalKembaliAwal' => Carbon::parse($tanggalAwal)->addDay()->toDateString(),
            'maksHari' => Pengaturan::angka(Pengaturan::LAMA_PINJAM_MAKS),
            'maksUnit' => Pengaturan::angka(Pengaturan::UNIT_MAKS_PER_AJUAN),
            'syarat' => Pengaturan::teks(Pengaturan::SYARAT_PEMINJAMAN),
            'estimasiHari' => Pengaturan::angka(Pengaturan::ESTIMASI_PENINJAUAN_HARI),
        ]);
    }

    private function tanggalAwal(mixed $nilai): string
    {
        try {
            $tanggal = is_string($nilai) && $nilai !== '' ? Carbon::parse($nilai)->startOfDay() : today();
        } catch (\Throwable) {
            $tanggal = today();
        }

        return $tanggal->max(today())->toDateString();
    }

    /** Unduh template surat: unggahan admin bila ada, selain itu template bawaan. */
    public function templateSurat(): BinaryFileResponse
    {
        $path = Pengaturan::teks(Pengaturan::TEMPLATE_SURAT);
        $file = $path && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->path($path)
            : resource_path('template/template-surat-peminjaman-alat.docx');

        return response()->download($file, 'Template Surat Permohonan Peminjaman Alat.'.pathinfo($file, PATHINFO_EXTENSION));
    }

    public function store(AjuanRequest $request, IdentitasPeminjamService $identitas, PeminjamanService $service): RedirectResponse
    {
        // Surat disimpan di disk privat (berisi data pribadi), hanya admin yang bisa membuka
        $surat = $request->file('surat_peminjaman')->store('surat', 'local');

        try {
            $peminjam = $identitas->cariAtauBuat($request->identitas());
            $peminjaman = $service->ajukan($peminjam, $request->ajuan() + ['surat_peminjaman' => $surat]);
        } catch (PeminjamanException $e) {
            Storage::disk('local')->delete($surat);

            return back()->withInput()->with('error', $e->getMessage());
        }

        // Pengaju langsung boleh melihat halaman status ajuannya di sesi ini
        StatusController::beriAkses($request, $peminjaman);

        return redirect()->route('peminjam.status.show', $peminjaman->kode_peminjaman)
            ->with('success', "Ajuan {$peminjaman->kode_peminjaman} berhasil dikirim. Simpan kode ini; rincian juga dikirim ke {$peminjam->email}.");
    }
}
