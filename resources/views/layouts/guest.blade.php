<!DOCTYPE html>
<html lang="id">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="bg-gradient-to-b from-marian-50 via-white to-marian-50 font-sans text-marian-950 antialiased">
        {{-- Halaman login/lupa password admin: panel gradasi + kartu form --}}
        <div class="mx-auto grid min-h-screen max-w-6xl items-center gap-6 p-3 sm:p-6 [grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr))]">
            <section class="relative hidden overflow-hidden rounded-5xl bg-gradient-to-br from-marian-950 via-marian-800 to-marian-500 p-10 text-white md:block">
                <div aria-hidden="true" class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/10"></div>
                <a href="{{ route('peminjam.katalog.index') }}" class="relative flex items-center gap-2.5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white/15"><x-ikon-alat nama="labu" class="h-6 w-6" /></span>
                    <span class="leading-tight">
                        <span class="block text-sm font-extrabold">Peminjaman Alat</span>
                        <span class="block text-xs text-marian-200">{{ config('peminjaman.mitra_singkat') }}</span>
                    </span>
                </a>
                <h1 class="relative mt-8 text-3xl font-extrabold leading-tight">Panel Petugas Laboratorium</h1>
                <p class="relative mt-2 text-sm text-marian-100">{{ config('peminjaman.mitra') }}</p>
                <div class="relative mx-auto mt-8 max-w-xs"><x-ilustrasi-lab /></div>
            </section>

            <section class="w-full rounded-5xl bg-white p-6 shadow-lembut ring-1 ring-marian-100 sm:p-10">
                <a href="{{ route('peminjam.katalog.index') }}" class="mb-6 flex items-center gap-2.5 md:hidden">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-marian-900 to-marian-600 text-white"><x-ikon-alat nama="labu" class="h-6 w-6" /></span>
                    <span class="leading-tight">
                        <span class="block text-sm font-extrabold text-marian-900">Peminjaman Alat</span>
                        <span class="block text-xs text-marian-500">{{ config('peminjaman.mitra_singkat') }}</span>
                    </span>
                </a>
                {{ $slot }}
            </section>
        </div>
    </body>
</html>
