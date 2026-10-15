<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * <x-app-layout> otomatis memilih layout:
     * halaman /admin (admin login) → sidebar; halaman publik peminjam → navbar.
     */
    public function render(): View
    {
        return auth()->user()?->isAdmin() && ! request()->routeIs('peminjam.*')
            ? view('layouts.admin')
            : view('layouts.peminjam');
    }
}
