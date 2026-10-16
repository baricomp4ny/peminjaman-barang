<?php

namespace App\Providers;

use App\View\Composers\AdminLayoutComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nama hari/bulan di tanggal memakai bahasa Indonesia (translatedFormat)
        Carbon::setLocale(config('app.locale'));

        // Paginasi tanpa teks "Menampilkan x sampai y dari n data" di semua halaman
        Paginator::defaultView('pagination.tanpa-ringkasan');

        // Menu sidebar admin
        View::composer('layouts.admin', AdminLayoutComposer::class);
    }
}
