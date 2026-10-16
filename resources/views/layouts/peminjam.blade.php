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
    <body class="kertas-grid font-sans text-marian-950 antialiased">
        <div class="flex min-h-screen flex-col">
            {{-- Navbar: putih polos dengan garis bawah, menu aktif = garis biru --}}
            <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-marian-950/15 bg-white/95 backdrop-blur">
                <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6">
                    <a href="{{ route('peminjam.katalog.index') }}" class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center border border-marian-800 text-marian-800">
                            <x-ikon-alat nama="labu" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 leading-tight">
                            <span class="block truncate text-sm font-semibold text-marian-950">Peminjaman Alat Lab</span>
                            <span class="block truncate font-mono text-[11px] text-marian-600">{{ config('peminjaman.mitra_singkat') }}</span>
                        </span>
                    </a>

                    {{-- Menu desktop --}}
                    <div class="hidden h-full items-stretch md:flex">
                        @foreach ($menu as $item)
                            @php $aktif = request()->routeIs($item['aktif']); @endphp
                            <a href="{{ route($item['route']) }}" @if ($aktif) aria-current="page" @endif
                               class="flex items-center border-b-2 px-4 text-sm font-medium transition {{ $aktif ? 'border-marian-800 text-marian-900' : 'border-transparent text-marian-950/60 hover:text-marian-900' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>

                    <div class="hidden md:block">
                        <a href="{{ $admin ? route('admin.dashboard') : route('login') }}"
                           class="border border-marian-950/20 px-4 py-2 font-mono text-xs font-medium uppercase tracking-wider text-marian-900 transition hover:border-marian-800 hover:bg-marian-800 hover:text-white">
                            {{ $admin ? 'Dashboard Admin' : 'Login Admin' }}
                        </a>
                    </div>

                    {{-- Tombol menu mobile --}}
                    <button @click="open = ! open" class="flex h-10 w-10 items-center justify-center border border-marian-950/15 text-marian-900 md:hidden" :aria-expanded="open" aria-label="Buka menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="square">
                            <path x-show="!open" d="M4 7h16M4 12h16M4 17h16" />
                            <path x-show="open" d="M6 6l12 12M18 6L6 18" style="display: none;" />
                        </svg>
                    </button>
                </nav>

                {{-- Menu mobile --}}
                <div x-show="open" x-transition @click.outside="open = false" style="display: none;"
                     class="border-t border-marian-950/10 bg-white md:hidden">
                    @foreach ($menu as $item)
                        @php $aktif = request()->routeIs($item['aktif']); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="block border-l-2 px-5 py-3 text-sm font-medium {{ $aktif ? 'border-marian-800 bg-marian-50 text-marian-900' : 'border-transparent text-marian-950/70' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    <a href="{{ $admin ? route('admin.dashboard') : route('login') }}" class="block border-l-2 border-transparent px-5 py-3 font-mono text-xs uppercase tracking-wider text-marian-700">
                        {{ $admin ? 'Dashboard Admin' : 'Login Admin' }}
                    </a>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:py-10">
                @include('layouts.partials.flash')

                @isset($header)
                    <div class="mb-6">{{ $header }}</div>
                @endisset

                {{ $slot }}
            </main>

            {{-- Footer polos bergaris atas --}}
            <footer class="mt-12 border-t border-marian-950/15 bg-white">
                <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 [grid-template-columns:repeat(auto-fit,minmax(min(100%,15rem),1fr))]">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center border border-marian-800 text-marian-800"><x-ikon-alat nama="labu" class="h-5 w-5" /></span>
                            <span class="text-sm font-semibold">Peminjaman Alat Lab</span>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-marian-950/70">{{ config('peminjaman.mitra') }}</p>
                    </div>
                    <div class="text-sm">
                        <div class="label-bagian">Lokasi & Layanan</div>
                        <p class="mt-3 text-marian-950/70">{{ config('peminjaman.alamat') }}</p>
                        <p class="mt-1 font-mono text-xs text-marian-950/70">{{ config('peminjaman.jam_layanan') }}</p>
                    </div>
                    <div class="text-sm">
                        <div class="label-bagian">Tautan</div>
                        <ul class="mt-3 space-y-1.5">
                            @foreach ($menu as $item)
                                <li><a href="{{ route($item['route']) }}" class="text-marian-950/70 hover:text-marian-800">→ {{ $item['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="border-t border-marian-950/10 px-4 py-4 text-center font-mono text-[11px] text-marian-950/60">
                    Peminjam tidak perlu login — ajukan, lalu cek status dengan kode peminjaman dan NIM.
                </div>
            </footer>
        </div>
    </body>
</html>
