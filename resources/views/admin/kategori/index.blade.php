<x-app-layout>
    <x-slot name="header"><span class="text-sm text-gray-500">Data Barang</span></x-slot>

    <x-admin.judul judul="Kategori">
        <x-link-button :href="route('admin.kategori.create')">+ Tambah Kategori</x-link-button>
    </x-admin.judul>

    <div class="rounded-lg bg-white shadow">
        <form method="GET" class="flex gap-2 border-b p-4">
            <x-text-input name="q" :value="request('q')" placeholder="Cari nama kategori…" class="w-full sm:w-72" />
            <x-secondary-button type="submit">Cari</x-secondary-button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Nama Kategori</th>
                        <th class="px-4 py-3">Jumlah Jenis Barang</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($kategori as $k)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $k->nama }}</td>
                            <td class="px-4 py-3">{{ $k->barang_count }}</td>
                            <td class="space-x-3 whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.kategori.edit', $k) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Ubah</a>
                                <x-admin.hapus :action="route('admin.kategori.destroy', $k)" :nama="'kategori '.$k->nama" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">Belum ada kategori{{ request('q') ? ' yang cocok dengan pencarian' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $kategori->links() }}</div>
    </div>
</x-app-layout>
