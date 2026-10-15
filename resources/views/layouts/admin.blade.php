@php
    /*
     * Menu sidebar admin (urutan sesuai alur kerja).
     * 'aktif'     = pola nama route (string/array) atau boolean untuk menandai menu yang dibuka.
     * 'pola'      = pola route milik satu grup/menu utama (untuk judul & subjudul halaman).
     * 'deskripsi' = tampil sebagai subjudul di bagian atas halaman.
     */
    $laporan = ['peminjaman' => 'Transaksi', 'pengembalian' => 'Pengembalian', 'inventaris' => 'Inventaris', 'keterlambatan' => 'Keterlambatan', 'denda' => 'Denda'];

    $menu = [
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
            'items' => collect($laporan)->map(fn ($label, $jenis) => [
                'label' => $label,
                'route' => 'admin.laporan.show',
                'params' => ['jenis' => $jenis],
                // aktif bila sedang membuka laporan jenis ini
                'aktif' => request()->routeIs('admin.laporan.show') && request()->route('jenis') === $jenis,
            ])->values()->all()],
        ['grup' => 'Pengaturan', 'pola' => ['admin.pengaturan.*', 'admin.akun-admin.*'],
            'deskripsi' => 'Pengelolaan data admin, aturan peminjaman, dan ketentuan denda',
            'items' => [
                ['label' => 'Data Admin', 'route' => 'admin.akun-admin.index', 'aktif' => 'admin.akun-admin.*'],
                ['label' => 'Aturan & Denda', 'route' => 'admin.pengaturan.edit', 'aktif' => 'admin.pengaturan.*'],
            ]],
    ];

    // Menu utama yang sedang dibuka → judul & subjudul di header halaman
    $menuAktif = collect($menu)->first(fn ($m) => request()->routeIs($m['pola']));

    // Jumlah ajuan menunggu untuk badge menu
    $jumlahAjuan = \App\Models\Peminjaman::where('status', \App\Models\Peminjaman::STATUS_MENUNGGU)->count();
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="bg-marian-50 font-sans text-marian-950 antialiased">
        <div x-data="{ sidebar: false }" class="min-h-screen">
            {{-- Latar gelap saat sidebar terbuka di layar kecil --}}
            <div x-show="sidebar" x-transition.opacity @click="sidebar = false"
                 class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden" style="display: none;"></div>

            {{-- Sidebar --}}
            <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-40 w-64 transform overflow-y-auto bg-gradient-to-b from-marian-950 via-marian-900 to-marian-700 text-marian-100 transition-transform duration-200 lg:translate-x-0">
                <div class="flex h-16 items-center gap-2 border-b border-white/10 px-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15 text-white"><x-ikon-alat nama="labu" class="h-5 w-5" /></span>
                    <div class="leading-tight">
                        <div class="text-sm font-extrabold text-white">Peminjaman Alat</div>
                        <div class="text-xs text-marian-300">Panel Admin · <a href="{{ route('peminjam.katalog.index') }}" class="hover:text-white">Lihat situs</a></div>
                    </div>
                </div>

                <nav class="space-y-1 px-3 py-4 text-sm">
                    @foreach ($menu as $item)
                        @if (isset($item['grup']))
                            @php $grupAktif = collect($item['items'])->contains(fn ($i) => isset($i['aktif']) && (is_bool($i['aktif']) ? $i['aktif'] : request()->routeIs($i['aktif']))); @endphp
                            <div x-data="{ buka: {{ $grupAktif ? 'true' : 'false' }} }">
                                <button type="button" @click="buka = !buka"
                                        class="flex w-full items-center justify-between rounded-xl px-3 py-2 font-semibold text-marian-100 hover:bg-white/10 hover:text-white">
                                    <span class="flex items-center gap-2">
                                        {{ $item['grup'] }}
                                        @if ($item['grup'] === 'Transaksi' && $jumlahAjuan > 0)
                                            <span class="rounded-full bg-white px-2 text-xs font-bold text-marian-800">{{ $jumlahAjuan }}</span>
                                        @endif
                                    </span>
                                    <svg :class="buka && 'rotate-90'" class="h-4 w-4 transition-transform" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.17 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
                                </button>
                                <div x-show="buka" x-transition class="mt-1 space-y-1 pl-3" @if (! $grupAktif) style="display: none;" @endif>
                                    @foreach ($item['items'] as $sub)
                                        @include('layouts.partials.admin-menu-item', ['item' => $sub, 'jumlahAjuan' => $jumlahAjuan])
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @include('layouts.partials.admin-menu-item', ['item' => $item, 'jumlahAjuan' => $jumlahAjuan])
                        @endif
                    @endforeach
                </nav>
            </aside>

            {{-- Konten --}}
            <div class="lg:pl-64">
                <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-marian-100 bg-white/90 px-4 backdrop-blur sm:px-6">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="sidebar = true" class="rounded-full p-2 text-marian-700 hover:bg-marian-50 lg:hidden" aria-label="Buka menu">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        @if ($menuAktif)
                            {{-- Judul menu + deskripsi sebagai subjudul halaman --}}
                            <div class="min-w-0 leading-tight">
                                <div class="font-extrabold text-marian-900">{{ $menuAktif['label'] ?? $menuAktif['grup'] }}</div>
                                <div class="hidden truncate text-xs text-marian-500 sm:block">{{ $menuAktif['deskripsi'] }}</div>
                            </div>
                        @elseif (isset($header))
                            <div>{{ $header }}</div>
                        @endif
                    </div>

                    {{-- Avatar: profil, ganti password, logout --}}
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 rounded-full text-sm font-semibold text-marian-800 hover:text-marian-950 focus:outline-none">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-marian-900 to-marian-600 font-bold text-white">
                                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>
                            <x-dropdown-link :href="route('profile.edit').'#ganti-password'">Ganti Password</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    Keluar
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </header>

                <main class="p-4 sm:p-6">
                    @include('layouts.partials.flash')
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
