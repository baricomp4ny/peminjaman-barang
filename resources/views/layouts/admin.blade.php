{{-- $menu, $menuAktif, $jumlahAjuan disiapkan oleh App\View\Composers\AdminLayoutComposer --}}
<!DOCTYPE html>
<html lang="id">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="kertas-grid font-sans text-marian-950 antialiased">
        <div x-data="{ sidebar: false }" class="min-h-screen">
            {{-- Latar gelap saat sidebar terbuka di layar kecil --}}
            <div x-show="sidebar" x-transition.opacity @click="sidebar = false"
                 class="fixed inset-0 z-30 bg-marian-950/40 lg:hidden" style="display: none;"></div>

            {{-- Sidebar: putih bergaris tepi, menu aktif ditandai garis biru di kiri --}}
            <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-40 w-64 transform overflow-y-auto border-r border-marian-950/15 bg-white transition-transform duration-200 lg:translate-x-0">
                <div class="flex h-16 items-center gap-3 border-b border-marian-950/15 px-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center border border-marian-800 text-marian-800"><x-ikon-alat nama="labu" class="h-5 w-5" /></span>
                    <div class="leading-tight">
                        <div class="text-sm font-semibold">Peminjaman Alat Lab</div>
                        <div class="font-mono text-[11px] text-marian-600">Panel Admin · <a href="{{ route('peminjam.katalog.index') }}" class="underline-offset-2 hover:underline">Lihat situs</a></div>
                    </div>
                </div>

                <nav class="py-4 text-sm">
                    @foreach ($menu as $item)
                        @if (isset($item['grup']))
                            <div x-data="{ buka: {{ $item['aktif'] ? 'true' : 'false' }} }" class="mt-1">
                                <button type="button" @click="buka = !buka"
                                        class="flex w-full items-center justify-between px-5 py-2 font-mono text-[11px] font-medium uppercase tracking-[0.16em] {{ $item['aktif'] ? 'text-marian-800' : 'text-marian-950/55 hover:text-marian-900' }}">
                                    <span class="flex items-center gap-2">
                                        {{ $item['grup'] }}
                                        @if (! empty($item['badge']) && $jumlahAjuan > 0)
                                            <span class="bg-marian-800 px-1.5 text-[10px] font-semibold text-white">{{ $jumlahAjuan }}</span>
                                        @endif
                                    </span>
                                    <svg :class="buka && 'rotate-90'" class="h-3.5 w-3.5 transition-transform" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M7.5 5l5 5-5 5"/></svg>
                                </button>
                                <div x-show="buka" x-transition class="pb-1" @if (! $item['aktif']) style="display: none;" @endif>
                                    @foreach ($item['items'] as $sub)
                                        @include('layouts.partials.admin-menu-item', ['item' => $sub, 'menjorok' => true])
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @include('layouts.partials.admin-menu-item', ['item' => $item])
                        @endif
                    @endforeach
                </nav>
            </aside>

            {{-- Konten --}}
            <div class="lg:pl-64">
                <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-marian-950/15 bg-white/95 px-4 backdrop-blur sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" @click="sidebar = true" class="border border-marian-950/15 p-2 text-marian-900 lg:hidden" aria-label="Buka menu">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="square"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        @if ($menuAktif)
                            {{-- Judul menu + deskripsi sebagai subjudul halaman --}}
                            <div class="min-w-0 leading-tight">
                                <div class="font-semibold text-marian-950">{{ $menuAktif['label'] ?? $menuAktif['grup'] }}</div>
                                <div class="hidden truncate text-xs text-marian-950/60 sm:block">{{ $menuAktif['deskripsi'] }}</div>
                            </div>
                        @elseif (isset($header))
                            <div>{{ $header }}</div>
                        @endif
                    </div>

                    {{-- Avatar: profil, ganti password, logout --}}
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 text-sm font-medium text-marian-950 hover:text-marian-800 focus:outline-none">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full border border-marian-800 font-mono text-sm font-semibold text-marian-800">
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
