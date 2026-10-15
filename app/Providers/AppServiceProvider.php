<?php

namespace App\Providers;

use App\View\Composers\AdminLayoutComposer;
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

        // Menu sidebar admin
        View::composer('layouts.admin', AdminLayoutComposer::class);
    }
}
