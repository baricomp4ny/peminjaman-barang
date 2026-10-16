@props(['barang', 'sisa' => null])

{{-- Kartu alat ala etalase toko: gambar dalam bingkai, nama, lalu baris bawah berisi kode & sisa unit --}}
<article class="group flex flex-col rounded-4xl bg-white p-3 shadow-lembut ring-1 ring-marian-100 transition hover:-translate-y-1 hover:ring-2 hover:ring-magenta focus-within:ring-2 focus-within:ring-magenta active:ring-2 active:ring-magenta">
    <a href="{{ route('peminjam.katalog.show', $barang) }}" class="relative block overflow-hidden rounded-3xl bg-gradient-to-br from-marian-50 to-marian-100">
        @if ($barang->foto)
            <img src="{{ Storage::url($barang->foto) }}" alt="{{ $barang->nama }}" loading="lazy" class="aspect-square w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex aspect-square w-full items-center justify-center text-marian-700 transition duration-300 group-hover:scale-105">
                <x-ikon-alat :nama="$barang->nama" class="h-20 w-20" />
            </div>
        @endif

        {{-- Pita sudut seperti label "Sale!" untuk alat yang habis --}}
        @if (! is_null($sisa) && $sisa <= 0)
            <span class="absolute left-3 top-3 rounded-full bg-white px-3 py-1 text-xs font-semibold text-marian-400 shadow-sm">Habis</span>
        @endif
    </a>

    <div class="flex flex-1 flex-col px-2 pb-1 pt-4">
        <a href="{{ route('peminjam.katalog.show', $barang) }}" class="font-bold leading-snug text-marian-900 hover:text-marian-700">{{ $barang->nama }}</a>
        <span class="mb-4 mt-0.5 text-xs text-marian-500">{{ $barang->kategori->nama }}</span>

        <div class="mt-auto flex items-center justify-between gap-2 border-t border-marian-100 pt-3">
            <span class="font-mono text-xs font-semibold text-marian-500">{{ $barang->kode }}</span>
            @if (! is_null($sisa))
                <span class="text-sm font-bold {{ $sisa > 0 ? 'text-marian-800' : 'text-marian-300' }}">{{ $sisa > 0 ? $sisa.' tersedia' : '0 tersedia' }}</span>
            @endif
        </div>

        <a href="{{ route('peminjam.ajuan.create', ['barang_id' => $barang->id]) }}"
           class="mt-3 inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-marian-900 to-marian-700 px-4 py-2 text-sm font-semibold text-white transition hover:from-marian-950 hover:to-marian-800"
           aria-label="Pinjam {{ $barang->nama }}">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
            Pinjam
        </a>
    </div>
</article>
