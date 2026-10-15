<?php

namespace App\View\Composers;

use App\Enums\StatusPeminjaman;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Data sidebar admin: daftar menu (urutan sesuai alur kerja), menu yang sedang dibuka,
 * dan jumlah ajuan menunggu untuk badge. Status aktif dihitung di sini agar Blade tinggal menampilkan.
 */
class AdminLayoutComposer
{
    private const LAPORAN = [
        'peminjaman' => 'Transaksi',
        'pengembalian' => 'Pengembalian',
        'inventaris' => 'Inventaris',
        'keterlambatan' => 'Keterlambatan',
        'denda' => 'Denda',
    ];

    public function __construct(private Request $request) {}

    public function compose(View $view): void
    {
        $menu = array_map(fn (array $m) => $this->tandaiAktif($m), $this->menu());

        $view->with([
            'menu' => $menu,
            // Menu utama yang sedang dibuka → judul & subjudul di header halaman
            'menuAktif' => collect($menu)->first(fn ($m) => $this->request->routeIs($m['pola'])),
            'jumlahAjuan' => Peminjaman::where('status', StatusPeminjaman::Menunggu)->count(),
        ]);
    }

    /**
     * 'aktif'     = pola nama route (string/array) atau boolean yang sudah dihitung.
     * 'pola'      = pola route milik menu utama/grup (untuk judul & subjudul halaman).
     * 'deskripsi' = subjudul di bagian atas halaman.
     * 'badge'     = tampilkan jumlah ajuan menunggu.
     */
    private function menu(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'aktif' => 'admin.dashboard', 'pola' => ['admin.dashboard'],
                'deskripsi' => 'Ringkasan inventaris dan transaksi aktif'],
            ['grup' => 'Data Barang', 'pola' => ['admin.barang.*', 'admin.unit.*', 'admin.kategori.*', 'admin.lokasi.*'],
                'deskripsi' => 'Pengelolaan data barang, jenis, kategori, unit, kondisi, lokasi, serta barcode',
                'items' => [
                    ['label' => 'Jenis Barang', 'route' => 'admin.barang.index', 'aktif' => 'admin.barang.*'],
                    ['label' => 'Unit & Kondisi', 'route' => 'admin.unit.index', 'aktif' => ['admin.unit.index', 'admin.unit.create', 'admin.unit.edit']],
                    ['label' => 'Kategori', 'route' => 'admin.kategori.index', 'aktif' => 'admin.kategori.*'],
                    ['label' => 'Lokasi', 'route' => 'admin.lokasi.index', 'aktif' => 'admin.lokasi.*'],
                    ['label' => 'Cetak Label Barcode', 'route' => 'admin.unit.label', 'aktif' => 'admin.unit.label'],
                ]],
            ['label' => 'Data Peminjam', 'route' => 'admin.peminjam.index', 'aktif' => 'admin.peminjam.*', 'pola' => ['admin.peminjam.*'],
                'deskripsi' => 'Pengelolaan identitas dan riwayat peminjaman setiap peminjam'],
            ['grup' => 'Transaksi', 'pola' => ['admin.ajuan.*', 'admin.peminjaman.*', 'admin.pengembalian.*', 'admin.riwayat.*'],
                'deskripsi' => 'Pengelolaan pengajuan, peminjaman aktif, pengembalian, dan riwayat transaksi',
                'badge' => true,
                'items' => [
                    ['label' => 'Ajuan Peminjaman', 'route' => 'admin.ajuan.index', 'aktif' => 'admin.ajuan.*', 'badge' => true],
                    ['label' => 'Peminjaman Aktif', 'route' => 'admin.peminjaman.index', 'aktif' => 'admin.peminjaman.*'],
                    ['label' => 'Pengembalian', 'route' => 'admin.pengembalian.index', 'aktif' => 'admin.pengembalian.*'],
                    ['label' => 'Riwayat Transaksi', 'route' => 'admin.riwayat.index', 'aktif' => 'admin.riwayat.*'],
                ]],
            ['label' => 'Scan Barcode', 'route' => 'admin.scan.index', 'aktif' => 'admin.scan.*', 'pola' => ['admin.scan.*'],
                'deskripsi' => 'Pemindaian barcode untuk mencatat penyerahan dan pengembalian barang'],
            ['grup' => 'Laporan', 'pola' => ['admin.laporan.*'],
                'deskripsi' => 'Rekapitulasi transaksi, pengembalian, inventaris, keterlambatan, dan denda',
                'items' => collect(self::LAPORAN)->map(fn ($label, $jenis) => [
                    'label' => $label,
                    'route' => 'admin.laporan.show',
                    'params' => ['jenis' => $jenis],
                    // aktif bila sedang membuka laporan jenis ini
                    'aktif' => $this->request->routeIs('admin.laporan.show') && $this->request->route('jenis') === $jenis,
                ])->values()->all()],
            ['grup' => 'Pengaturan', 'pola' => ['admin.pengaturan.*', 'admin.akun-admin.*'],
                'deskripsi' => 'Pengelolaan data admin, aturan peminjaman, dan ketentuan denda',
                'items' => [
                    ['label' => 'Data Admin', 'route' => 'admin.akun-admin.index', 'aktif' => 'admin.akun-admin.*'],
                    ['label' => 'Aturan & Denda', 'route' => 'admin.pengaturan.edit', 'aktif' => 'admin.pengaturan.*'],
                ]],
        ];
    }

    /** Ubah pola 'aktif' menjadi boolean; grup aktif bila salah satu submenunya aktif. */
    private function tandaiAktif(array $item): array
    {
        if (isset($item['items'])) {
            $item['items'] = array_map(fn (array $i) => $this->tandaiAktif($i), $item['items']);
            $item['aktif'] = collect($item['items'])->contains('aktif', true);

            return $item;
        }

        $item['aktif'] = is_bool($item['aktif']) ? $item['aktif'] : $this->request->routeIs($item['aktif']);

        return $item;
    }
}
