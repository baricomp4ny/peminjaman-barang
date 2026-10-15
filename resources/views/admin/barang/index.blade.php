<x-app-layout>
    <x-slot name="header"><span class="text-sm text-gray-500">Data Barang</span></x-slot>

    <x-admin.judul judul="Jenis Barang">
        <x-link-button :href="route('admin.barang.create')">+ Tambah Jenis Barang</x-link-button>
    </x-admin.judul>

    <div class="rounded-lg bg-white shadow">
        <form method="GET" class="flex flex-col gap-2 border-b p-4 sm:flex-row">
            <x-text-input name="q" :value="request('q')" placeholder="Cari nama atau kode…" class="w-full sm:w-72" />
            <x-select name="kategori_id" class="w-full sm:w-56">
                <option value="">Semua kategori</option>
                @foreach ($kategori as $k)
                    <option value="{{ $k->id }}" @selected(request('kategori_id') == $k->id)>{{ $k->nama }}</option>
                @endforeach
            </x-select>
            <x-secondary-button type="submit">Filter</x-secondary-button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Barang</th>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Unit (tersedia / total)</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($barang as $b)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($b->foto)
                                        <img src="{{ Storage::url($b->foto) }}" alt="" class="h-10 w-10 rounded object-cover">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded bg-gray-100 text-xs text-gray-400">—</div>
                                    @endif
                                    <a href="{{ route('admin.barang.show', $b) }}" class="font-medium text-gray-800 hover:text-indigo-600">{{ $b->nama }}</a>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono">{{ $b->kode }}</td>
                            <td class="px-4 py-3">{{ $b->kategori->nama }}</td>
                            <td class="px-4 py-3">{{ $b->unit_tersedia_count }} / {{ $b->unit_count }}</td>
                            <td class="space-x-3 whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.barang.show', $b) }}" class="font-medium text-gray-600 hover:text-gray-800">Detail</a>
                                <a href="{{ route('admin.barang.edit', $b) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Ubah</a>
                                <x-admin.hapus :action="route('admin.barang.destroy', $b)" :nama="'jenis barang '.$b->nama" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada jenis barang{{ request()->hasAny(['q', 'kategori_id']) ? ' yang cocok dengan filter' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $barang->links() }}</div>
    </div>
</x-app-layout>
