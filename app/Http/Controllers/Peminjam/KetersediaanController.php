<?php

namespace App\Http\Controllers\Peminjam;

use App\Http\Controllers\Controller;
use App\Services\KetersediaanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Endpoint JSON untuk form ajuan: sisa unit per barang pada rentang tanggal. */
class KetersediaanController extends Controller
{
    public function __invoke(Request $request, KetersediaanService $ketersediaan): JsonResponse
    {
        $data = $request->validate([
            'mulai' => ['required', 'date'],
            'selesai' => ['required', 'date', 'after_or_equal:mulai'],
            'barang' => ['required', 'array', 'max:50'],
            'barang.*' => ['integer'],
        ]);

        $hasil = collect($ketersediaan->hitung($data['barang'], $data['mulai'], $data['selesai']))
            ->map(fn ($s) => $s['sisa']);

        return response()->json($hasil);
    }
}
