@props(['barang', 'sisa' => null])

{{-- Kartu alat ala katalog: area gambar lembut + bar biru berisi nama & tombol bulat "Pinjam" --}}
<article class="group flex flex-col rounded-4xl bg-white p-3 shadow-lembut ring-1 ring-marian-100 transition hover:-translate-y-1 hover:ring-marian-300">
    <a href="{{ route('peminjam.katalog.show', $barang) }}" class="relative block overflow-hidden rounded-3xl bg-gradient-to-br from-marian-50 to-marian-100">
        @if ($barang->foto)
            <img src="{{ Storage::url($barang->foto) }}" alt="{{ $barang->nama }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex aspect-[4/3] w-full items-center justify-center text-marian-700 transition duration-300 group-hover:scale-105">
                <x-ikon-alat :nama="$barang->nama" class="h-20 w-20" />
            </div>
        @endif

        @if (! is_null($sisa))
            <span class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-semibold shadow-sm {{ $sisa > 0 ? 'bg-marian-800 text-white' : 'bg-white text-marian-400' }}">
                {{ $sisa > 0 ? $sisa.' tersedia' : 'Habis hari ini' }}
            </span>
        @endif
        <span class="absolute right-3 top-3 rounded-full bg-white/90 px-2.5 py-1 font-mono text-[11px] font-semibold text-marian-700">{{ $barang->kode }}</span>
    </a>

    <div class="mt-3 flex items-center gap-3 rounded-3xl bg-gradient-to-r from-marian-900 to-marian-700 px-4 py-3 text-white">
        <a href="{{ route('peminjam.katalog.show', $barang) }}" class="min-w-0 flex-1">
            <span class="block truncate text-sm font-semibold">{{ $barang->nama }}</span>
            <span class="block truncate text-xs text-marian-200">{{ $barang->kategori->nama }}</span>
        </a>
        <a href="{{ route('peminjam.ajuan.create', ['barang_id' => $barang->id]) }}"
           class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-marian-800 transition hover:bg-marian-100"
           title="Pinjam {{ $barang->nama }}" aria-label="Pinjam {{ $barang->nama }}">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
        </a>
    </div>
</article>
