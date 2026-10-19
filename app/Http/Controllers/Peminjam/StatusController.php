<?php

namespace App\Http\Controllers\Peminjam;

use App\Exceptions\PeminjamanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Peminjam\CekStatusRequest;
use App\Models\Peminjaman;
use App\Services\IdentitasPeminjamService;
use App\Services\PeminjamanService;
use App\Services\QrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Cek status ajuan tanpa login.
 * Akses ke halaman status diberikan bila:
 *  - peminjam memasukkan kode peminjaman + nomor identitas yang cocok, atau
 *  - membuka tautan bertanda tangan dari email, atau
 *  - baru saja mengirim ajuan di sesi yang sama.
 * Akses disimpan di session (per ajuan) supaya tidak perlu memasukkan ulang.
 */
class StatusController extends Controller
{
    private const KUNCI_SESI = 'akses_peminjaman';

    public static function beriAkses(Request $request, Peminjaman $p): void
    {
        $request->session()->put(self::KUNCI_SESI, array_values(array_unique(
            array_merge($request->session()->get(self::KUNCI_SESI, []), [$p->id])
        )));
    }

    /** Tautan untuk email: bertanda tangan, tetap berlaku sampai APP_KEY diganti. */
    public static function tautan(Peminjaman $p): string
    {
        return URL::signedRoute('peminjam.status.show', ['kode' => $p->kode_peminjaman]);
    }

    /**
     * Tautan pembatalan untuk email (bertanda tangan, berlaku sampai tanggal rencana kembali).
     * Pembatalan tetap hanya untuk status menunggu / disetujui yang belum diambil.
     */
    public static function tautanBatal(Peminjaman $p): string
    {
        return URL::temporarySignedRoute(
            'peminjam.status.batal.konfirmasi',
            $p->tanggal_rencana_kembali->copy()->endOfDay(),
            ['kode' => $p->kode_peminjaman],
        );
    }

    /** Halaman konfirmasi dari tautan email (middleware signed). */
    public function konfirmasiBatal(Request $request, string $kode): View
    {
        $p = Peminjaman::with(['peminjam', 'detail.barang'])->where('kode_peminjaman', strtoupper($kode))->firstOrFail();

        return view('peminjam.status.batal', [
            'peminjaman' => $p,
            // Form POST memakai URL yang sama (beserta tanda tangannya)
            'aksi' => $request->fullUrl(),
        ]);
    }

    public function form(): View
    {
        return view('peminjam.status.form');
    }

    public function cek(CekStatusRequest $request): RedirectResponse
    {
        $p = Peminjaman::with('peminjam')->where('kode_peminjaman', $request->kode)->first();

        // Pesan sama untuk kode salah maupun NIM salah agar data tidak bisa ditebak
        if (! $p || $p->peminjam->nomor_identitas !== $request->nomor_identitas) {
            return back()->withInput()->withErrors(['kode' => 'Kode peminjaman atau nomor identitas tidak cocok.']);
        }

        self::beriAkses($request, $p);

        return redirect()->route('peminjam.status.show', $p->kode_peminjaman);
    }

    public function show(Request $request, string $kode, PeminjamanService $service, QrService $qr, IdentitasPeminjamService $identitas): View|RedirectResponse
    {
        $p = Peminjaman::where('kode_peminjaman', strtoupper($kode))->first();

        if ($p && $request->hasValidSignature()) {
            self::beriAkses($request, $p);
        }

        if (! $p || ! in_array($p->id, $request->session()->get(self::KUNCI_SESI, []), true)) {
            return redirect()->route('peminjam.status.form')
                ->with('error', 'Masukkan kode peminjaman dan nomor identitas untuk melihat status ajuan.');
        }

        $p->load(['peminjam', 'detail.barang', 'peminjamanUnit.unitBarang.barang', 'verifikator']);

        return view('peminjam.status.show', [
            'peminjaman' => $p,
            'batasAmbil' => $service->batasAmbil($p),
            // QR berisi kode peminjaman; ditunjukkan ke petugas saat mengambil barang
            'qr' => $qr->svg($p->kode_peminjaman, 200),
            'emailSamar' => $identitas->samarkan($p->peminjam->email),
        ]);
    }

    public function batal(Request $request, string $kode, PeminjamanService $service): RedirectResponse
    {
        $p = Peminjaman::with('peminjam')->where('kode_peminjaman', strtoupper($kode))->firstOrFail();

        // Akses dari sesi (kode + NIM) atau dari tautan bertanda tangan di email
        $dariTautan = $request->hasValidSignature();
        if (! $dariTautan && ! in_array($p->id, $request->session()->get(self::KUNCI_SESI, []), true)) {
            abort(403, 'Anda tidak memiliki akses ke ajuan ini.');
        }
        if ($dariTautan) {
            self::beriAkses($request, $p);
        }

        try {
            $service->batalkan($p, $p->peminjam);
        } catch (PeminjamanException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('peminjam.status.show', $p->kode_peminjaman)
            ->with('success', "Ajuan {$p->kode_peminjaman} berhasil dibatalkan.");
    }
}
