<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * <x-app-layout> otomatis memakai layout sesuai peran:
     * admin → sidebar, peminjam → navbar.
     */
    public function render(): View
    {
        return auth()->user()?->isAdmin()
            ? view('layouts.admin')
            : view('layouts.peminjam');
    }
}
