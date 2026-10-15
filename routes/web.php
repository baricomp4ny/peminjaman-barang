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

    // Scan QR
    Route::get('/scan', [Admin\ScanController::class, 'index'])->name('scan.index');
    Route::post('/scan/cari', [Admin\ScanController::class, 'cari'])->name('scan.cari');
    Route::post('/scan/cek-unit', [Admin\ScanController::class, 'cekUnit'])->name('scan.cek-unit');

    // QR unit & cetak label (didaftarkan sebelum resource agar "label" tidak dianggap {unit})
    Route::get('/unit/label', [Admin\QrController::class, 'label'])->name('unit.label');
    Route::get('/unit/{unit}/qr', [Admin\QrController::class, 'unit'])->name('unit.qr');

    // Data master
    Route::resource('kategori', Admin\KategoriController::class)->except('show');
    Route::resource('lokasi', Admin\LokasiController::class)->except('show');
    Route::resource('barang', Admin\BarangController::class);
    Route::resource('unit', Admin\UnitBarangController::class)->except('show');
    Route::resource('peminjam', Admin\PeminjamController::class);

    // Transaksi
    Route::get('/ajuan', [Admin\AjuanController::class, 'index'])->name('ajuan.index');
    Route::post('/ajuan/{peminjaman}/setujui', [Admin\AjuanController::class, 'setujui'])->name('ajuan.setujui');
    Route::post('/ajuan/{peminjaman}/tolak', [Admin\AjuanController::class, 'tolak'])->name('ajuan.tolak');

    Route::get('/peminjaman', [Admin\PeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::get('/peminjaman/{peminjaman}', [Admin\PeminjamanController::class, 'show'])->name('peminjaman.show');
    Route::get('/peminjaman/{peminjaman}/serah', [Admin\PeminjamanController::class, 'serahForm'])->name('peminjaman.serah');
    Route::post('/peminjaman/{peminjaman}/serah', [Admin\PeminjamanController::class, 'serahkan'])->name('peminjaman.serahkan');

    Route::get('/pengembalian', [Admin\PengembalianController::class, 'index'])->name('pengembalian.index');
    Route::get('/pengembalian/{peminjaman}', [Admin\PengembalianController::class, 'create'])->name('pengembalian.create');
    Route::post('/pengembalian/{peminjaman}', [Admin\PengembalianController::class, 'store'])->name('pengembalian.store');

    Route::get('/riwayat', [Admin\RiwayatController::class, 'index'])->name('riwayat.index');

    // Laporan
    Route::get('/laporan', [Admin\LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/{jenis}', [Admin\LaporanController::class, 'show'])
        ->whereIn('jenis', ['peminjaman', 'pengembalian', 'inventaris', 'keterlambatan', 'denda'])
        ->name('laporan.show');

    // Pengaturan aturan bisnis
    Route::get('/pengaturan', [Admin\PengaturanController::class, 'edit'])->name('pengaturan.edit');
    Route::put('/pengaturan', [Admin\PengaturanController::class, 'update'])->name('pengaturan.update');
});

// ---------- Peminjam ----------
Route::middleware(['auth', 'role:peminjam'])->prefix('peminjam')->name('peminjam.')->group(function () {
    Route::get('/beranda', [Peminjam\BerandaController::class, 'index'])->name('beranda');

    Route::get('/katalog', [Peminjam\KatalogController::class, 'index'])->name('katalog.index');
    Route::get('/katalog/{barang}', [Peminjam\KatalogController::class, 'show'])->name('katalog.show');
    Route::get('/ketersediaan', Peminjam\KetersediaanController::class)->name('ketersediaan');

    Route::get('/ajuan/create', [Peminjam\AjuanController::class, 'create'])->name('ajuan.create');
    Route::post('/ajuan', [Peminjam\AjuanController::class, 'store'])->name('ajuan.store');

    Route::get('/pinjaman', [Peminjam\PinjamanController::class, 'index'])->name('pinjaman.index');
    Route::get('/pinjaman/{peminjaman}', [Peminjam\PinjamanController::class, 'show'])->name('pinjaman.show');
    Route::post('/pinjaman/{peminjaman}/batal', [Peminjam\PinjamanController::class, 'batal'])->name('pinjaman.batal');

    Route::get('/kartu-qr', Peminjam\KartuQrController::class)->name('kartu-qr');

    Route::get('/notifikasi', [Peminjam\NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [Peminjam\NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{id}', [Peminjam\NotifikasiController::class, 'buka'])->name('notifikasi.buka');
});

require __DIR__.'/auth.php';
