<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('peminjam.katalog.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Katalog</a>
        <h1 class="text-xl font-semibold leading-tight text-gray-800">{{ $barang->nama }}</h1>
    </x-slot>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="overflow-hidden rounded-lg bg-white shadow">
            @if ($barang->foto)
                <img src="{{ Storage::url($barang->foto) }}" alt="{{ $barang->nama }}" class="w-full object-cover">
            @else
                <div class="flex aspect-video items-center justify-center bg-gray-100 text-gray-400">Tidak ada foto</div>
            @endif
        </div>
        <div class="rounded-lg bg-white p-6 shadow">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-500">Kategori</dt><dd class="font-medium">{{ $barang->kategori->nama }}</dd></div>
                <div><dt class="text-gray-500">Deskripsi</dt><dd>{{ $barang->deskripsi ?: '—' }}</dd></div>
                <div>
                    <dt class="text-gray-500">Ketersediaan hari ini</dt>
                    <dd class="font-medium {{ $stok['sisa'] > 0 ? 'text-green-700' : 'text-red-600' }}">{{ $stok['sisa'] }} dari {{ $stok['kapasitas'] }} unit</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-gray-500">Ketersediaan pada tanggal lain dicek otomatis saat Anda mengisi form ajuan.</p>
            <x-link-button class="mt-4" :href="route('peminjam.ajuan.create', ['barang_id' => $barang->id])">Ajukan Peminjaman</x-link-button>
        </div>
    </div>
</x-app-layout>
