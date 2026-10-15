<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Peminjam;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Halaman awal: tamu ke login, yang sudah login ke halaman sesuai peran
Route::get('/', function () {
    return auth()->check() ? redirect(auth()->user()->homeRoute()) : redirect()->route('login');
});

// Tujuan bawaan setelah login/registrasi (RouteServiceProvider::HOME)
Route::get('/dashboard', function () {
    return redirect(auth()->user()->homeRoute());
})->middleware('auth')->name('dashboard');

// Profil & ganti password untuk kedua peran
Route::middleware(['auth', 'role:admin,peminjam'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// ---------- Admin ----------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
});

// ---------- Peminjam ----------
Route::middleware(['auth', 'role:peminjam'])->prefix('peminjam')->name('peminjam.')->group(function () {
    Route::get('/beranda', [Peminjam\BerandaController::class, 'index'])->name('beranda');
});

require __DIR__.'/auth.php';
