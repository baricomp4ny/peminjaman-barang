<?php

namespace App\Http\Controllers\Peminjam;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function index(): View
    {
        // Ringkasan pinjaman dikerjakan di Sprint 3
        return view('peminjam.beranda');
    }
}
