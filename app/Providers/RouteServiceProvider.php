<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // {peminjam} hanya cocok dengan user ber-role peminjam; ID admin → 404
        Route::bind('peminjam', fn ($id) => User::peminjam()->findOrFail($id));
        // {akunAdmin} hanya cocok dengan user ber-role admin; ID peminjam → 404
        Route::bind('akunAdmin', fn ($id) => User::admin()->findOrFail($id));

        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
