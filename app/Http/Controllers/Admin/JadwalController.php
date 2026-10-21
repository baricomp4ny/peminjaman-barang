<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\JadwalRequest;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Services\JadwalService;
use Illuminate\View\View;

/** Jadwal Pemakaian Alat versi admin: lengkap dengan kode peminjaman, nama peminjam, dan ajuan menunggu. */
class JadwalController extends Controller
{
    public function __invoke(JadwalRequest $request, JadwalService $jadwal): View
    {
        return view('admin.jadwal.index', [
            'data' => $jadwal->susun($request->mulai(), $request->filter(), publik: false),
            'mulai' => $request->mulai(),
            'tab' => $request->tab(),
            'kategori' => Kategori::orderBy('nama')->get(),
            'lokasi' => Lokasi::orderBy('nama')->get(),
        ]);
    }
}
