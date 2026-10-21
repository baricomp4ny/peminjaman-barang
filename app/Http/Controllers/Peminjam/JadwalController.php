<?php

namespace App\Http\Controllers\Peminjam;

use App\Http\Controllers\Controller;
use App\Http\Requests\JadwalRequest;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Services\JadwalService;
use Illuminate\View\View;

/** Jadwal Pemakaian Alat versi publik: hanya status terisi/kosong, tanpa identitas peminjam. */
class JadwalController extends Controller
{
    public function __invoke(JadwalRequest $request, JadwalService $jadwal): View
    {
        return view('peminjam.jadwal.index', [
            'data' => $jadwal->susun($request->mulai(), $request->filter(), publik: true),
            'mulai' => $request->mulai(),
            'tab' => $request->tab(),
            'kategori' => Kategori::orderBy('nama')->get(),
            'lokasi' => Lokasi::orderBy('nama')->get(),
        ]);
    }
}
