<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-950/55">Data Barang</span></x-slot>

    <x-admin.judul judul="Lokasi">
        <x-link-button :href="route('admin.lokasi.create')">+ Tambah Lokasi</x-link-button>
    </x-admin.judul>

    <div class="rounded-3xl bg-white border border-marian-950/15">
        <form method="GET" class="flex gap-2 border-b p-4">
            <x-text-input name="q" :value="request('q')" placeholder="Cari nama/keterangan…" class="w-full sm:w-72" />
            <x-secondary-button type="submit">Cari</x-secondary-button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-marian-950/10 text-sm">
                <thead class="bg-marian-50 text-left text-xs font-semibold uppercase text-marian-950/55">
                    <tr>
                        <th class="px-4 py-3">Nama Lokasi</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3">Jumlah Unit</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-marian-950/10">
                    @forelse ($lokasi as $l)
                        <tr>
                            <td class="px-4 py-3 font-medium text-marian-900">{{ $l->nama }}</td>
                            <td class="px-4 py-3 text-marian-950/65">{{ $l->keterangan ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.unit.index', ['lokasi_id' => $l->id]) }}" class="text-indigo-600 hover:underline">{{ $l->unit_barang_count }}</a>
                            </td>
                            <td class="space-x-3 whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.lokasi.edit', $l) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Ubah</a>
                                <x-admin.hapus :action="route('admin.lokasi.destroy', $l)" :nama="'lokasi '.$l->nama" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-marian-950/55">Belum ada lokasi{{ request('q') ? ' yang cocok dengan pencarian' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $lokasi->links() }}</div>
    </div>
</x-app-layout>
