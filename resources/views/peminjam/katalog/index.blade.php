<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold leading-tight text-gray-800">Katalog Barang</h1>
    </x-slot>

    <form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
        <x-text-input name="q" :value="request('q')" placeholder="Cari barang…" class="w-full sm:w-72" />
        <x-select name="kategori_id" class="w-full sm:w-56">
            <option value="">Semua kategori</option>
            @foreach ($kategori as $k)
                <option value="{{ $k->id }}" @selected(request('kategori_id') == $k->id)>{{ $k->nama }}</option>
            @endforeach
        </x-select>
        <x-secondary-button type="submit">Cari</x-secondary-button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($barang as $b)
            @php $sisa = $stok[$b->id]['sisa']; @endphp
            <div class="flex flex-col overflow-hidden rounded-lg bg-white shadow">
                @if ($b->foto)
                    <img src="{{ Storage::url($b->foto) }}" alt="{{ $b->nama }}" class="aspect-video w-full object-cover">
                @else
                    <div class="flex aspect-video w-full items-center justify-center bg-gray-100 text-sm text-gray-400">Tidak ada foto</div>
                @endif
                <div class="flex flex-1 flex-col p-4">
                    <div class="text-xs text-gray-500">{{ $b->kategori->nama }}</div>
                    <a href="{{ route('peminjam.katalog.show', $b) }}" class="font-semibold text-gray-800 hover:text-indigo-600">{{ $b->nama }}</a>
                    <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ $b->deskripsi }}</p>
                    <div class="mt-auto flex items-center justify-between pt-4">
                        <span class="text-sm {{ $sisa > 0 ? 'text-green-700' : 'text-red-600' }}">
                            {{ $sisa > 0 ? "Tersedia hari ini: {$sisa} unit" : 'Tidak tersedia hari ini' }}
                        </span>
                        <x-link-button :href="route('peminjam.ajuan.create', ['barang_id' => $b->id])" class="!px-3">Pinjam</x-link-button>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-gray-500">Barang tidak ditemukan.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $barang->links() }}</div>
</x-app-layout>
