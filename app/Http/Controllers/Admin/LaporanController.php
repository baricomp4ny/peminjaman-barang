<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LaporanRequest;
use App\Services\LaporanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class LaporanController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.laporan.show', 'peminjaman');
    }

    /** Tampilkan laporan di web, atau unduh sebagai PDF/Excel (?format=pdf|excel). */
    public function show(LaporanRequest $request, string $jenis, LaporanService $service): Response|\Illuminate\View\View
    {
        $laporan = $service->buat($jenis, $request->dari(), $request->sampai());
        $namaFile = 'laporan-'.$jenis.'-'.now()->format('Ymd-His');

        return match ($request->input('format', 'web')) {
            'pdf' => Pdf::loadView('admin.laporan.pdf', ['laporan' => $laporan])
                ->setPaper('a4', 'landscape')
                ->download($namaFile.'.pdf'),
            'excel' => Excel::download(new LaporanExport($laporan), $namaFile.'.xlsx'),
            default => view('admin.laporan.show', [
                'laporan' => $laporan,
                'dari' => $request->dari()->toDateString(),
                'sampai' => $request->sampai()->toDateString(),
            ]),
        };
    }
}
