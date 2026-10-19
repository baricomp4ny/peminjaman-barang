<?php

namespace App\Services;

use App\Models\Peminjaman;
use Barryvdh\DomPDF\Facade\Pdf;

/** PDF bukti peminjaman: dipakai tombol cetak di admin dan lampiran email persetujuan. */
class BuktiPeminjamanService
{
    public function __construct(private QrService $qr, private PeminjamanService $peminjaman) {}

    public function namaFile(Peminjaman $p): string
    {
        return 'bukti-'.$p->kode_peminjaman.'.pdf';
    }

    /** Isi PDF (biner). */
    public function pdf(Peminjaman $p): string
    {
        $p->loadMissing(['peminjam', 'verifikator', 'detail.barang', 'peminjamanUnit.unitBarang.barang']);

        // QR SVG ditanam sebagai data URI supaya tidak perlu file sementara
        $qr = 'data:image/svg+xml;base64,'.base64_encode($this->qr->svg($p->kode_peminjaman, 160));

        // Subset font: hanya huruf yang dipakai yang ditanam, supaya lampiran email kecil (±25 KB)
        return Pdf::loadView('pdf.bukti-peminjaman', [
            'p' => $p,
            'qr' => $qr,
            'batasAmbil' => $this->peminjaman->batasAmbil($p),
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true)->output();
    }
}
