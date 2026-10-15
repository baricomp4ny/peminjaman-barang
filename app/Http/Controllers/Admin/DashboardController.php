<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Isi statistik dikerjakan di Sprint 3
        return view('admin.dashboard');
    }
}
