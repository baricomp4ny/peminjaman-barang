@php
    // Menu navbar peminjam. 'route' = null artinya fitur belum dibuat (ditandai "segera").
    $menu = [
        ['label' => 'Beranda', 'route' => 'peminjam.beranda', 'aktif' => 'peminjam.beranda'],
        ['label' => 'Katalog Barang', 'route' => null],
        ['label' => 'Ajukan Peminjaman', 'route' => null],
        ['label' => 'Pinjaman Saya', 'route' => null],
        ['label' => 'Notifikasi', 'route' => null],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="min-h-screen">
            <nav x-data="{ open: false }" class="border-b border-gray-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <a href="{{ route('peminjam.beranda') }}" class="flex shrink-0 items-center gap-2">
                                <x-application-logo class="h-8 w-8 fill-current text-indigo-600" />
                                <span class="hidden text-sm font-semibold text-gray-800 md:inline">Peminjaman Barang</span>
                            </a>

                            {{-- Menu desktop --}}
                            <div class="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                                @foreach ($menu as $item)
                                    @if ($item['route'])
                                        <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['aktif'])">
                                            {{ $item['label'] }}
                                        </x-nav-link>
                                    @else
                                        <span class="inline-flex cursor-not-allowed items-center gap-1 border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-400" title="Segera hadir">
                                            {{ $item['label'] }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        {{-- Profil (kartu QR, logout) --}}
                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 focus:outline-none">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 font-semibold text-white">
                                            {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                                        </span>
                                        <span>{{ Auth::user()->name }}</span>
                                        <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>
                                    <span class="block cursor-not-allowed px-4 py-2 text-sm text-gray-400" title="Segera hadir">Kartu QR</span>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                            Keluar
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
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
                            @if ($item['route'])
                                <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['aktif'])">
                                    {{ $item['label'] }}
                                </x-responsive-nav-link>
                            @else
                                <span class="block cursor-not-allowed border-l-4 border-transparent py-2 pe-4 ps-3 text-base font-medium text-gray-400">
                                    {{ $item['label'] }} <span class="text-xs">(segera)</span>
                                </span>
                            @endif
                        @endforeach
                    </div>

                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                            <div class="text-sm font-medium text-gray-500">{{ Auth::user()->email }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">Profil</x-responsive-nav-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    Keluar
                                </x-responsive-nav-link>
                            </form>
                        </div>
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

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                @include('layouts.partials.flash')
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
