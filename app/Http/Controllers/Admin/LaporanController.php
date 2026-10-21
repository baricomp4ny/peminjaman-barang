<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LaporanRequest;
use App\Services\LaporanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LaporanController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.laporan.show', 'pengembalian');
    }

    /** Tampilkan laporan di web, atau unduh sebagai PDF/Excel (?format=pdf|excel). */
    public function show(LaporanRequest $request, string $jenis, LaporanService $service): Response|View
    {
        $laporan = $service->buat($jenis, $request->dari(), $request->sampai());
        $format = $request->input('format', 'web');

        return $format !== 'web'
            ? $service->unduh($laporan, $format)
            : view('admin.laporan.show', [
                'laporan' => $laporan,
                'dari' => $request->dari()->toDateString(),
                'sampai' => $request->sampai()->toDateString(),
            ]);
    }
}
