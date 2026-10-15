<x-app-layout>
    <a href="{{ route('peminjam.katalog.index') }}#katalog" class="inline-flex items-center gap-1 text-sm font-semibold text-marian-700 hover:text-marian-900">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 12H5M11 6l-6 6 6 6" /></svg>
        Katalog Alat
    </a>

    <div class="mt-4 grid gap-6 [grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr))]">
        {{-- Gambar / ikon --}}
        <div class="relative overflow-hidden rounded-5xl bg-gradient-to-br from-marian-900 via-marian-700 to-marian-500 p-6">
            <div aria-hidden="true" class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/10"></div>
            @if ($barang->foto)
                <img src="{{ Storage::url($barang->foto) }}" alt="{{ $barang->nama }}" class="relative aspect-[4/3] w-full rounded-4xl object-cover">
            @else
                <div class="relative flex aspect-[4/3] w-full items-center justify-center rounded-4xl bg-white/10 text-white ring-1 ring-white/20">
                    <x-ikon-alat :nama="$barang->nama" class="h-32 w-32" />
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div class="flex flex-col rounded-5xl bg-white p-6 shadow-lembut ring-1 ring-marian-100 sm:p-8">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-marian-50 px-3 py-1 text-xs font-semibold text-marian-700 ring-1 ring-marian-200">{{ $barang->kategori->nama }}</span>
                <span class="rounded-full bg-marian-50 px-3 py-1 font-mono text-xs font-semibold text-marian-700 ring-1 ring-marian-200">{{ $barang->kode }}</span>
            </div>
            <h1 class="mt-3 font-extrabold text-marian-900 [font-size:clamp(1.6rem,3vw,2.4rem)]">{{ $barang->nama }}</h1>
            <p class="mt-2 text-sm leading-relaxed text-marian-700">{{ $barang->deskripsi ?: 'Belum ada deskripsi.' }}</p>

            <dl class="mt-6 grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,9rem),1fr))]">
                <div class="rounded-3xl bg-gradient-to-br from-marian-800 to-marian-600 p-4 text-white">
                    <dt class="text-xs text-marian-100">Tersedia hari ini</dt>
                    <dd class="mt-1 text-3xl font-extrabold">{{ $stok['sisa'] }}<span class="text-base font-semibold text-marian-200"> / {{ $stok['kapasitas'] }}</span></dd>
                </div>
                <div class="rounded-3xl bg-marian-50 p-4 ring-1 ring-marian-100">
                    <dt class="text-xs text-marian-600">Lama pinjam maks.</dt>
                    <dd class="mt-1 text-3xl font-extrabold text-marian-900">{{ $maksHari }}<span class="text-base font-semibold text-marian-500"> hari</span></dd>
                </div>
            </dl>

            @if ($lokasi->isNotEmpty())
                <p class="mt-4 text-sm text-marian-600"><span class="font-semibold text-marian-800">Lokasi:</span> {{ $lokasi->join(', ') }}</p>
            @endif

            <div class="mt-auto flex flex-wrap gap-3 pt-6">
                <a href="{{ route('peminjam.ajuan.create', ['barang_id' => $barang->id]) }}" class="inline-flex items-center gap-2 rounded-full bg-marian-800 px-6 py-3 text-sm font-bold text-white transition hover:bg-marian-900">
                    Ajukan Peminjaman
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </a>
            </div>
            <p class="mt-3 text-xs text-marian-500">Ketersediaan di tanggal lain dihitung otomatis saat Anda memilih tanggal di form ajuan.</p>
        </div>
    </div>

    @if ($lainnya->isNotEmpty())
        <section class="mt-12">
            <h2 class="text-xl font-extrabold text-marian-900">Alat lain di {{ $barang->kategori->nama }}</h2>
            <div class="mt-5 grid gap-5 [grid-template-columns:repeat(auto-fill,minmax(min(100%,15rem),1fr))]">
                @foreach ($lainnya as $b)
                    <x-kartu-alat :barang="$b" :sisa="$stokLain[$b->id]['sisa'] ?? 0" />
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
