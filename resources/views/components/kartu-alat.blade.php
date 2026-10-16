@props(['barang', 'sisa' => null])

{{-- Kartu alat ala label spesimen: kode mono di pojok, gambar di bingkai bergaris, indikator sisa unit --}}
<article class="group flex flex-col border border-marian-950/15 bg-white transition hover:border-marian-800">
    <div class="flex items-center justify-between border-b border-marian-950/10 px-3 py-2 font-mono text-[11px]">
        <span class="font-semibold text-marian-800">{{ $barang->kode }}</span>
        <span class="truncate pl-2 text-marian-950/55">{{ $barang->kategori->nama }}</span>
    </div>

    <a href="{{ route('peminjam.katalog.show', $barang) }}" class="relative block overflow-hidden bg-marian-50/40">
        @if ($barang->foto)
            <img src="{{ Storage::url($barang->foto) }}" alt="{{ $barang->nama }}" loading="lazy" class="aspect-[4/3] w-full object-cover grayscale-[20%] transition duration-300 group-hover:grayscale-0">
        @else
            <div class="flex aspect-[4/3] w-full items-center justify-center text-marian-800/80">
                <x-ikon-alat :nama="$barang->nama" class="h-16 w-16" />
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col border-t border-marian-950/10 p-3">
        <a href="{{ route('peminjam.katalog.show', $barang) }}" class="text-sm font-semibold leading-snug text-marian-950 hover:text-marian-800">{{ $barang->nama }}</a>

        @if (! is_null($sisa))
            {{-- Indikator sisa unit hari ini --}}
            <div class="mt-3 flex items-center gap-2 font-mono text-[11px]">
                <span class="flex gap-0.5" aria-hidden="true">
                    @for ($i = 0; $i < min(max($sisa, 0), 10); $i++)
                        <span class="h-3 w-1.5 bg-marian-800"></span>
                    @endfor
                    @if ($sisa <= 0)
                        <span class="h-3 w-1.5 border border-marian-950/30"></span>
                    @endif
                </span>
                <span class="{{ $sisa > 0 ? 'text-marian-800' : 'text-marian-950/45' }}">{{ $sisa > 0 ? $sisa.' unit tersedia' : 'Habis hari ini' }}</span>
            </div>
        @endif

        <a href="{{ route('peminjam.ajuan.create', ['barang_id' => $barang->id]) }}"
           class="mt-3 inline-flex items-center justify-between border-t border-marian-950/10 pt-3 text-xs font-semibold text-marian-800 hover:text-marian-950"
           aria-label="Pinjam {{ $barang->nama }}">
            Pinjam alat ini <span aria-hidden="true">→</span>
        </a>
    </div>
</article>
