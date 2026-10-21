<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Peminjam;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Peminjam TIDAK login: katalog, form ajuan, dan cek status bersifat publik.
| Login hanya untuk admin (semua route /admin dilindungi middleware role:admin).
*/

// ---------- Publik (peminjam) ----------
Route::name('peminjam.')->group(function () {
    Route::get('/', [Peminjam\KatalogController::class, 'index'])->name('katalog.index');
    Route::get('/katalog/{barang}', [Peminjam\KatalogController::class, 'show'])->name('katalog.show');
    Route::get('/jadwal', Peminjam\JadwalController::class)->name('jadwal');
    Route::get('/ketersediaan', Peminjam\KetersediaanController::class)->middleware('throttle:60,1')->name('ketersediaan');

    Route::get('/ajuan', [Peminjam\AjuanController::class, 'create'])->name('ajuan.create');
    Route::get('/ajuan/template-surat', [Peminjam\AjuanController::class, 'templateSurat'])->name('ajuan.template');
    // Dibatasi supaya form publik tidak dibanjiri kiriman
    Route::post('/ajuan', [Peminjam\AjuanController::class, 'store'])->middleware('throttle:10,1')->name('ajuan.store');

    Route::get('/status', [Peminjam\StatusController::class, 'form'])->name('status.form');
    Route::post('/status', [Peminjam\StatusController::class, 'cek'])->middleware('throttle:10,1')->name('status.cek');
    Route::get('/status/{kode}', [Peminjam\StatusController::class, 'show'])->name('status.show');
    // Pembatalan dari tautan bertanda tangan di email: GET = halaman konfirmasi, POST = eksekusi
    Route::get('/status/{kode}/batal', [Peminjam\StatusController::class, 'konfirmasiBatal'])->middleware('signed')->name('status.batal.konfirmasi');
    Route::post('/status/{kode}/batal', [Peminjam\StatusController::class, 'batal'])->middleware('throttle:10,1')->name('status.batal');
});

// Tujuan bawaan setelah login (RouteServiceProvider::HOME)
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))->middleware('auth')->name('dashboard');

// ---------- Admin ----------
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Scan Barcode
    Route::get('/scan', [Admin\ScanController::class, 'index'])->name('scan.index');
    Route::post('/scan/cari', [Admin\ScanController::class, 'cari'])->name('scan.cari');
    Route::post('/scan/cek-unit', [Admin\ScanController::class, 'cekUnit'])->name('scan.cek-unit');

    // QR unit & cetak label (didaftarkan sebelum resource agar "label" tidak dianggap {unit})
    Route::get('/unit/label', [Admin\QrController::class, 'label'])->name('unit.label');
    Route::get('/unit/{unit}/qr', [Admin\QrController::class, 'unit'])->name('unit.qr');

    // Data Barang & Data Peminjam
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
    Route::get('/peminjaman/{peminjaman}/bukti', [Admin\PeminjamanController::class, 'bukti'])->name('peminjaman.bukti');
    Route::get('/peminjaman/{peminjaman}/surat', [Admin\PeminjamanController::class, 'surat'])->name('peminjaman.surat');
    Route::get('/peminjaman/{peminjaman}/serah', [Admin\PeminjamanController::class, 'serahForm'])->name('peminjaman.serah');
    Route::post('/peminjaman/{peminjaman}/serah', [Admin\PeminjamanController::class, 'serahkan'])->name('peminjaman.serahkan');

    Route::get('/pengembalian', [Admin\PengembalianController::class, 'index'])->name('pengembalian.index');
    Route::get('/pengembalian/{peminjaman}', [Admin\PengembalianController::class, 'create'])->name('pengembalian.create');
    Route::post('/pengembalian/{peminjaman}', [Admin\PengembalianController::class, 'store'])->name('pengembalian.store');

    Route::get('/riwayat', [Admin\RiwayatController::class, 'index'])->name('riwayat.index');
    Route::get('/jadwal', Admin\JadwalController::class)->name('jadwal');

    // Laporan
    Route::get('/laporan', [Admin\LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/{jenis}', [Admin\LaporanController::class, 'show'])
        ->whereIn('jenis', ['peminjaman', 'pengembalian', 'inventaris', 'keterlambatan', 'statistik'])
        ->name('laporan.show');

    // Pengaturan: data admin + aturan peminjaman
    Route::redirect('/pengaturan', '/admin/pengaturan/aturan');
    Route::get('/pengaturan/aturan', [Admin\PengaturanController::class, 'edit'])->name('pengaturan.edit');
    Route::put('/pengaturan/aturan', [Admin\PengaturanController::class, 'update'])->name('pengaturan.update');
    Route::resource('pengaturan/admin', Admin\AkunAdminController::class)
        ->except('show')
        ->parameters(['admin' => 'akunAdmin'])
        ->names('akun-admin');
});

require __DIR__.'/auth.php';
