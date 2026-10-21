<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Data Barang</span></x-slot>

    <x-admin.judul judul="Kategori">
        <x-link-button :href="route('admin.kategori.create')">+ Tambah Kategori</x-link-button>
    </x-admin.judul>

    <div class="rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100" x-data="pilihBanyak(@js($kategori->pluck('id')))">
        <form method="GET" class="flex gap-2 border-b p-4">
            <x-text-input name="q" :value="request('q')" placeholder="Cari nama kategori…" class="w-full sm:w-72" />
            <x-secondary-button type="submit">Cari</x-secondary-button>
        </form>

        <x-admin.bilah-pilih :aksi="route('admin.kategori.hapus-terpilih')" sebutan="kategori" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-marian-100 text-sm">
                <thead class="bg-marian-50 text-left text-xs font-semibold uppercase text-marian-500">
                    <tr>
                        <th class="w-8 px-4 py-3"><input type="checkbox" class="rounded border-marian-300 text-marian-800" aria-label="Pilih semua di halaman ini" :checked="semuaDipilih" :indeterminate="sebagianDipilih" @change="pilihSemua($event.target.checked)"></th>
                        <th class="px-4 py-3">Nama Kategori</th>
                        <th class="px-4 py-3">Jumlah Jenis Barang</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-marian-50">
                    @forelse ($kategori as $k)
                        <tr>
                            <td class="px-4 py-3"><input type="checkbox" value="{{ $k->id }}" x-model="dipilih" class="rounded border-marian-300 text-marian-800" aria-label="Pilih {{ $k->nama }}"></td>
                            <td class="px-4 py-3 font-medium text-marian-900">{{ $k->nama }}</td>
                            <td class="px-4 py-3">{{ $k->barang_count }}</td>
                            <td class="space-x-3 whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.kategori.edit', $k) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Ubah</a>
                                <x-admin.hapus :action="route('admin.kategori.destroy', $k)" :nama="'kategori '.$k->nama" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-marian-500">Belum ada kategori{{ request('q') ? ' yang cocok dengan pencarian' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $kategori->links() }}</div>
    </div>
</x-app-layout>
