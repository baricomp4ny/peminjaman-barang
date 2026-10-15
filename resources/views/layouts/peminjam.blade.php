@php
    // Navbar publik untuk peminjam (tanpa login)
    $menu = [
        ['label' => 'Katalog Barang', 'route' => 'peminjam.katalog.index', 'aktif' => 'peminjam.katalog.*'],
        ['label' => 'Ajukan Peminjaman', 'route' => 'peminjam.ajuan.create', 'aktif' => 'peminjam.ajuan.*'],
        ['label' => 'Cek Status', 'route' => 'peminjam.status.form', 'aktif' => 'peminjam.status.*'],
    ];
    $admin = auth()->user()?->isAdmin();
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="flex min-h-screen flex-col">
            <nav x-data="{ open: false }" class="border-b border-gray-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <a href="{{ route('peminjam.katalog.index') }}" class="flex shrink-0 items-center gap-2">
                                <x-application-logo class="h-8 w-8 fill-current text-indigo-600" />
                                <span class="hidden leading-tight md:block">
                                    <span class="block text-sm font-semibold text-gray-800">Peminjaman Barang</span>
                                    <span class="block text-xs text-gray-500">{{ config('peminjaman.mitra') }}</span>
                                </span>
                            </a>

                            {{-- Menu desktop --}}
                            <div class="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                                @foreach ($menu as $item)
                                    <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['aktif'])">
                                        {{ $item['label'] }}
                                    </x-nav-link>
                                @endforeach
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center">
                            @if ($admin)
                                <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Dashboard Admin &rarr;</a>
                            @else
                                <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-gray-700">Login Admin</a>
                            @endif
                        </div>

                        {{-- Tombol menu mobile --}}
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none" aria-label="Buka menu">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Menu mobile --}}
                <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
                    <div class="space-y-1 pb-3 pt-2">
                        @foreach ($menu as $item)
                            <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['aktif'])">
                                {{ $item['label'] }}
                            </x-responsive-nav-link>
                        @endforeach
                        <x-responsive-nav-link :href="$admin ? route('admin.dashboard') : route('login')">
                            {{ $admin ? 'Dashboard Admin' : 'Login Admin' }}
                        </x-responsive-nav-link>
                    </div>
                </div>
            </nav>

            @isset($header)
                <header class="bg-white shadow">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @include('layouts.partials.flash')
                {{ $slot }}
            </main>

            <footer class="border-t bg-white py-4 text-center text-xs text-gray-500">
                {{ config('peminjaman.mitra') }} · Peminjam tidak perlu login: ajukan peminjaman, lalu cek status dengan kode peminjaman dan NIM.
            </footer>
        </div>
    </body>
</html>
