<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LabelRequest;
use App\Models\Barang;
use App\Models\Lokasi;
use App\Models\UnitBarang;
use App\Services\QrService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QrController extends Controller
{
    /** Gambar QR satu unit (SVG). ?unduh=1 untuk menyimpan sebagai file. */
    public function unit(UnitBarang $unit, QrService $qr): Response
    {
        // Ukuran dibatasi agar tidak bisa diminta gambar raksasa
        $ukuran = max(80, min(600, (int) request('ukuran', 240)));
        $response = response($qr->svg($unit->kode_unit, $ukuran))
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'private, max-age=86400');

        if (request()->boolean('unduh')) {
            $response->header('Content-Disposition', 'attachment; filename="qr-'.$unit->kode_unit.'.svg"');
        }

        return $response;
    }

    /** Halaman cetak label massal: QR + kode unit + nama barang. */
    public function label(LabelRequest $request, QrService $qr): View
    {
        $unit = UnitBarang::with('barang')
            ->when($request->filled('unit'), fn ($q) => $q->whereIn('id', $request->input('unit')))
            ->when($request->filled('barang_id'), fn ($q) => $q->where('barang_id', $request->barang_id))
            ->when($request->filled('lokasi_id'), fn ($q) => $q->where('lokasi_id', $request->lokasi_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('kode_unit')
            ->limit(500)
            ->get();

        // SVG dibuat sekali per unit di server, lalu disisipkan langsung (tidak perlu request gambar per label)
        $svg = $unit->mapWithKeys(fn ($u) => [$u->id => $qr->svg($u->kode_unit, 140)]);

        return view('admin.unit.label', [
            'unit' => $unit,
            'svg' => $svg,
            'kolom' => (int) $request->input('kolom', 4),
            'barang' => Barang::orderBy('nama')->get(['id', 'nama', 'kode']),
            'lokasi' => Lokasi::orderBy('nama')->get(['id', 'nama']),
        ]);
    }
}
