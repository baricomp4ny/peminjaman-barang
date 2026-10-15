<?php

namespace App\Http\Controllers\Peminjam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Peminjam\KetersediaanRequest;
use App\Services\KetersediaanService;
use Illuminate\Http\JsonResponse;

/** Endpoint JSON untuk form ajuan: sisa unit per alat pada rentang tanggal. */
class KetersediaanController extends Controller
{
    public function __invoke(KetersediaanRequest $request, KetersediaanService $ketersediaan): JsonResponse
    {
        $stok = $ketersediaan->hitung($request->validated('barang'), $request->validated('mulai'), $request->validated('selesai'));

        return response()->json(collect($stok)->map(fn ($s) => $s['sisa']));
    }
}
