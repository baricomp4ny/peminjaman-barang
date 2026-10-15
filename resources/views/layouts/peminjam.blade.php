@php
    // Navbar publik untuk peminjam (tanpa login)
    $menu = [
        ['label' => 'Katalog Alat', 'route' => 'peminjam.katalog.index', 'aktif' => 'peminjam.katalog.*'],
        ['label' => 'Ajukan Peminjaman', 'route' => 'peminjam.ajuan.create', 'aktif' => 'peminjam.ajuan.*'],
        ['label' => 'Cek Status', 'route' => 'peminjam.status.form', 'aktif' => 'peminjam.status.*'],
    ];
    $admin = auth()->user()?->isAdmin();
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="bg-gradient-to-b from-marian-50 via-white to-marian-50 font-sans text-marian-950 antialiased">
        <div class="flex min-h-screen flex-col">
            {{-- Navbar: pil putih melayang --}}
            <header x-data="{ open: false }" class="sticky top-0 z-40 px-3 pt-3 sm:px-6">
                <nav class="mx-auto flex max-w-7xl items-center justify-between gap-3 rounded-full bg-white/90 px-3 py-2 shadow-lembut ring-1 ring-marian-100 backdrop-blur sm:px-4">
                    <a href="{{ route('peminjam.katalog.index') }}" class="flex min-w-0 items-center gap-2.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-marian-900 to-marian-600 text-white">
                            <x-ikon-alat nama="labu" class="h-6 w-6" />
                        </span>
                        <span class="min-w-0 leading-tight">
                            <span class="block truncate text-sm font-extrabold text-marian-900">Peminjaman Alat</span>
                            <span class="block truncate text-[11px] font-medium text-marian-500">{{ config('peminjaman.mitra_singkat') }}</span>
                        </span>
                    </a>

                    {{-- Menu desktop --}}
                    <div class="hidden items-center gap-1 md:flex">
                        @foreach ($menu as $item)
                            @php $aktif = request()->routeIs($item['aktif']); @endphp
                            <a href="{{ route($item['route']) }}" @if ($aktif) aria-current="page" @endif
                               class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $aktif ? 'bg-marian-800 text-white shadow-sm' : 'text-marian-700 hover:bg-marian-50' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>

                    <div class="hidden md:block">
                        <a href="{{ $admin ? route('admin.dashboard') : route('login') }}"
                           class="rounded-full border border-marian-200 px-4 py-2 text-sm font-semibold text-marian-800 transition hover:bg-marian-800 hover:text-white">
                            {{ $admin ? 'Dashboard Admin' : 'Login Admin' }}
                        </a>
                    </div>

                    {{-- Tombol menu mobile --}}
                    <button @click="open = ! open" class="flex h-10 w-10 items-center justify-center rounded-full text-marian-800 hover:bg-marian-50 md:hidden" :aria-expanded="open" aria-label="Buka menu">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path x-show="!open" d="M4 7h16M4 12h16M4 17h16" />
                            <path x-show="open" d="M6 6l12 12M18 6L6 18" style="display: none;" />
                        </svg>
                    </button>
                </nav>

                {{-- Menu mobile --}}
                <div x-show="open" x-transition @click.outside="open = false" style="display: none;"
                     class="mx-auto mt-2 max-w-7xl space-y-1 rounded-4xl bg-white p-3 shadow-lembut ring-1 ring-marian-100 md:hidden">
                    @foreach ($menu as $item)
                        <a href="{{ route($item['route']) }}"
                           class="block rounded-full px-4 py-2.5 text-sm font-semibold {{ request()->routeIs($item['aktif']) ? 'bg-marian-800 text-white' : 'text-marian-800 hover:bg-marian-50' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    <a href="{{ $admin ? route('admin.dashboard') : route('login') }}" class="block rounded-full px-4 py-2.5 text-sm font-semibold text-marian-500 hover:bg-marian-50">
                        {{ $admin ? 'Dashboard Admin' : 'Login Admin' }}
                    </a>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl flex-1 px-3 py-6 sm:px-6 lg:py-8">
                @include('layouts.partials.flash')

                @isset($header)
                    <div class="mb-6">{{ $header }}</div>
                @endisset

                {{ $slot }}
            </main>

            {{-- Footer --}}
            <footer class="mt-10 rounded-t-5xl bg-gradient-to-br from-marian-950 via-marian-900 to-marian-700 text-marian-100">
                <div class="mx-auto grid max-w-7xl gap-8 px-6 py-10 [grid-template-columns:repeat(auto-fit,minmax(min(100%,15rem),1fr))]">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white"><x-ikon-alat nama="labu" class="h-6 w-6" /></span>
                            <span class="text-base font-extrabold text-white">Peminjaman Alat</span>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-marian-200">{{ config('peminjaman.mitra') }}</p>
                    </div>
                    <div class="text-sm">
                        <div class="font-semibold text-white">Lokasi & Layanan</div>
                        <p class="mt-2 text-marian-200">{{ config('peminjaman.alamat') }}</p>
                        <p class="mt-1 text-marian-200">{{ config('peminjaman.jam_layanan') }}</p>
                    </div>
                    <div class="text-sm">
                        <div class="font-semibold text-white">Tautan</div>
                        <ul class="mt-2 space-y-1.5">
                            @foreach ($menu as $item)
                                <li><a href="{{ route($item['route']) }}" class="text-marian-200 hover:text-white">{{ $item['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="border-t border-white/10 px-6 py-4 text-center text-xs text-marian-300">
                    Peminjam tidak perlu login — ajukan, lalu cek status dengan kode peminjaman dan NIM.
                </div>
            </footer>
        </div>
    </body>
</html>
