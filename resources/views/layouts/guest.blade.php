<!DOCTYPE html>
<html lang="id">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="kertas-grid font-sans text-marian-950 antialiased">
        {{-- Halaman login/lupa password admin: panel info + form, keduanya putih bergaris tepi --}}
        <div class="mx-auto grid min-h-screen max-w-5xl items-center gap-0 p-4 sm:p-6 [grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr))]">
            <section class="panel hidden h-full flex-col justify-between border-r-0 p-10 md:flex">
                <a href="{{ route('peminjam.katalog.index') }}" class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center border border-marian-800 text-marian-800"><x-ikon-alat nama="labu" class="h-5 w-5" /></span>
                    <span class="leading-tight">
                        <span class="block text-sm font-semibold">Peminjaman Alat Lab</span>
                        <span class="block font-mono text-[11px] text-marian-600">{{ config('peminjaman.mitra_singkat') }}</span>
                    </span>
                </a>
                <div>
                    <div class="label-bagian">Akses petugas</div>
                    <h1 class="mt-3 text-3xl font-semibold leading-tight">Panel Petugas Laboratorium</h1>
                    <p class="mt-2 text-sm text-marian-950/70">{{ config('peminjaman.mitra') }}</p>
                </div>
                <div class="mx-auto w-full max-w-xs text-marian-800"><x-ilustrasi-lab /></div>
            </section>

            <section class="panel w-full p-6 sm:p-10">
                <a href="{{ route('peminjam.katalog.index') }}" class="mb-6 flex items-center gap-3 md:hidden">
                    <span class="flex h-9 w-9 items-center justify-center border border-marian-800 text-marian-800"><x-ikon-alat nama="labu" class="h-5 w-5" /></span>
                    <span class="leading-tight">
                        <span class="block text-sm font-semibold">Peminjaman Alat Lab</span>
                        <span class="block font-mono text-[11px] text-marian-600">{{ config('peminjaman.mitra_singkat') }}</span>
                    </span>
                </a>
                {{ $slot }}
            </section>
        </div>
    </body>
</html>
