<?php

namespace App\Http\Controllers\Peminjam;

use App\Http\Controllers\Controller;
use App\Services\QrService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Kartu QR milik peminjam yang sedang login (dipakai saat mengambil barang). */
class KartuQrController extends Controller
{
    public function __invoke(Request $request, QrService $qr): View
    {
        $user = $request->user();

        return view('peminjam.kartu-qr', [
            'user' => $user,
            'svg' => $qr->svg($qr->isiKartu($user), 200),
        ]);
    }
}
