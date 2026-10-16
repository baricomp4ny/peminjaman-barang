<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Data Barang</span></x-slot>

    <x-admin.judul :judul="$barang->nama" :kembali="route('admin.barang.index')">
        <x-link-button variant="secondary" :href="route('admin.unit.label', ['barang_id' => $barang->id])" target="_blank">Cetak Label QR</x-link-button>
        <x-link-button variant="secondary" :href="route('admin.barang.edit', $barang)">Ubah</x-link-button>
        <x-link-button :href="route('admin.unit.create', ['barang_id' => $barang->id])">+ Tambah Unit</x-link-button>
    </x-admin.judul>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl bg-white p-6 shadow-lembut ring-1 ring-marian-100">
            @if ($barang->foto)
                <img src="{{ Storage::url($barang->foto) }}" alt="Foto {{ $barang->nama }}" class="mb-4 aspect-video w-full rounded object-cover">
            @endif
            <dl class="space-y-3 text-sm">
                <div><dt class="text-marian-500">Kode</dt><dd class="font-mono font-medium">{{ $barang->kode }}</dd></div>
                <div><dt class="text-marian-500">Kategori</dt><dd class="font-medium">{{ $barang->kategori->nama }}</dd></div>
                <div><dt class="text-marian-500">Deskripsi</dt><dd>{{ $barang->deskripsi ?: '—' }}</dd></div>
                <div>
                    <dt class="text-marian-500">Unit</dt>
                    <dd>{{ $unit->where('status', 'tersedia')->count() }} tersedia dari {{ $unit->count() }} unit</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100 lg:col-span-2">
            <div class="border-b px-4 py-3 font-semibold text-marian-900">Daftar Unit</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-marian-100 text-sm">
                    <thead class="bg-marian-50 text-left text-xs font-semibold uppercase text-marian-500">
                        <tr>
                            <th class="px-4 py-3">Kode Unit</th>
                            <th class="px-4 py-3">Lokasi</th>
                            <th class="px-4 py-3">Kondisi</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-marian-50">
                        @forelse ($unit as $u)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 font-mono font-medium">{{ $u->kode_unit }}</td>
                                <td class="px-4 py-3">{{ $u->lokasi->nama }}</td>
                                <td class="px-4 py-3"><x-admin.badge :value="$u->kondisi" /></td>
                                <td class="px-4 py-3"><x-admin.badge :value="$u->status" /></td>
                                <td class="space-x-3 whitespace-nowrap px-4 py-3 text-right">
                                    <a href="{{ route('admin.unit.edit', $u) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Ubah</a>
                                    <x-admin.hapus :action="route('admin.unit.destroy', $u)" :nama="'unit '.$u->kode_unit" />
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-marian-500">Belum ada unit. Klik "Tambah Unit" untuk membuat unit dengan kode otomatis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
