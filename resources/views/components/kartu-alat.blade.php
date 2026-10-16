@props(['barang', 'sisa' => null])

{{-- Kartu alat ala etalase toko: gambar dalam bingkai, nama, lalu baris bawah berisi kode & sisa unit --}}
<article class="group flex min-w-0 flex-col rounded-3xl bg-white p-2 lg:rounded-4xl lg:p-3 shadow-lembut ring-1 ring-marian-100 transition hover:-translate-y-1 hover:ring-2 hover:ring-magenta focus-within:ring-2 focus-within:ring-magenta active:ring-2 active:ring-magenta">
    <a href="{{ route('peminjam.katalog.show', $barang) }}" class="relative block overflow-hidden rounded-2xl bg-gradient-to-br lg:rounded-3xl from-marian-50 to-marian-100">
        @if ($barang->foto)
            <img src="{{ Storage::url($barang->foto) }}" alt="{{ $barang->nama }}" loading="lazy" class="aspect-square w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex aspect-square w-full items-center justify-center text-marian-700 transition duration-300 group-hover:scale-105">
                <x-ikon-alat :nama="$barang->nama" class="h-12 w-12 lg:h-20 lg:w-20" />
            </div>
        @endif

        {{-- Pita sudut seperti label "Sale!" untuk alat yang habis --}}
        @if (! is_null($sisa) && $sisa <= 0)
            <span class="absolute left-3 top-3 rounded-full bg-white px-3 py-1 text-xs font-semibold text-marian-400 shadow-sm">Habis</span>
        @endif
    </a>

    <div class="flex flex-1 flex-col px-1 pb-1 pt-3 lg:px-2 lg:pt-4">
        <a href="{{ route('peminjam.katalog.show', $barang) }}" class="text-sm font-bold leading-snug text-marian-900 hover:text-marian-700 lg:text-base">{{ $barang->nama }}</a>
        <span class="mb-3 mt-0.5 line-clamp-1 text-[11px] text-marian-500 lg:mb-4 lg:text-xs">{{ $barang->kategori->nama }}</span>

        <div class="mt-auto flex flex-wrap items-center justify-between gap-x-2 gap-y-0.5 border-t border-marian-100 pt-2 lg:pt-3">
            <span class="font-mono text-[11px] font-semibold text-marian-500 lg:text-xs">{{ $barang->kode }}</span>
            @if (! is_null($sisa))
                <span class="text-xs font-bold lg:text-sm {{ $sisa > 0 ? 'text-marian-800' : 'text-marian-300' }}">{{ $sisa > 0 ? $sisa.' tersedia' : '0 tersedia' }}</span>
            @endif
        </div>

        <a href="{{ route('peminjam.ajuan.create', ['barang_id' => $barang->id]) }}"
           class="mt-2 inline-flex items-center justify-center gap-1.5 rounded-full bg-gradient-to-r from-marian-900 to-marian-700 px-3 py-1.5 text-xs font-semibold lg:mt-3 lg:gap-2 lg:px-4 lg:py-2 lg:text-sm text-white transition hover:from-marian-950 hover:to-marian-800"
           aria-label="Pinjam {{ $barang->nama }}">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
            Pinjam
        </a>
    </div>
</article>
