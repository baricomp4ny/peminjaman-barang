<x-app-layout>
    <a href="{{ route('peminjam.katalog.index') }}#katalog" class="font-mono text-xs uppercase tracking-wider text-marian-800 hover:text-marian-950">← Katalog Alat</a>

    {{-- Lembar spesifikasi alat --}}
    <div class="panel mt-4 grid [grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr))]">
        <div class="relative border-b border-marian-950/15 p-6 md:border-b-0 md:border-r">
            <span class="absolute left-6 top-6 font-mono text-[11px] text-marian-950/45">FIG. {{ $barang->kode }}</span>
            @if ($barang->foto)
                <img src="{{ Storage::url($barang->foto) }}" alt="{{ $barang->nama }}" class="mt-6 aspect-[4/3] w-full border border-marian-950/10 object-cover">
            @else
                <div class="mt-6 flex aspect-[4/3] w-full items-center justify-center border border-dashed border-marian-950/20 text-marian-800">
                    <x-ikon-alat :nama="$barang->nama" class="h-28 w-28" />
                </div>
            @endif
        </div>

        <div class="flex flex-col p-6 sm:p-8">
            <div class="label-bagian">{{ $barang->kode }} / {{ $barang->kategori->nama }}</div>
            <h1 class="mt-3 font-semibold tracking-tight [font-size:clamp(1.6rem,3vw,2.3rem)]">{{ $barang->nama }}</h1>
            <p class="mt-2 text-sm leading-relaxed text-marian-950/70">{{ $barang->deskripsi ?: 'Belum ada deskripsi.' }}</p>

            <table class="mt-6 w-full border-t border-marian-950/15 text-sm">
                <tbody class="divide-y divide-marian-950/10">
                    <tr>
                        <td class="py-2.5 text-marian-950/65">Tersedia hari ini</td>
                        <td class="py-2.5 text-right font-mono font-semibold text-marian-800">{{ $stok['sisa'] }} / {{ $stok['kapasitas'] }} unit</td>
                    </tr>
                    <tr>
                        <td class="py-2.5 text-marian-950/65">Lama pinjam maks.</td>
                        <td class="py-2.5 text-right font-mono font-semibold">{{ $maksHari }} hari</td>
                    </tr>
                    @if ($lokasi->isNotEmpty())
                        <tr>
                            <td class="py-2.5 text-marian-950/65">Lokasi</td>
                            <td class="py-2.5 text-right">{{ $lokasi->join(', ') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <div class="mt-auto pt-6">
                <x-link-button :href="route('peminjam.ajuan.create', ['barang_id' => $barang->id])" class="px-6 py-3 text-sm">Ajukan Peminjaman →</x-link-button>
                <p class="mt-3 text-xs text-marian-950/55">Ketersediaan di tanggal lain dihitung otomatis saat Anda memilih tanggal di form ajuan.</p>
            </div>
        </div>
    </div>

    @if ($lainnya->isNotEmpty())
        <section class="mt-12">
            <div class="label-bagian">Alat lain di {{ $barang->kategori->nama }}</div>
            <div class="mt-4 grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(min(100%,15rem),1fr))]">
                @foreach ($lainnya as $b)
                    <x-kartu-alat :barang="$b" :sisa="$stokLain[$b->id]['sisa'] ?? 0" />
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
