<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-950/55">Data Peminjam</span></x-slot>

    <x-admin.judul judul="Data Peminjam">
        <x-link-button :href="route('admin.peminjam.create')">+ Tambah Peminjam</x-link-button>
    </x-admin.judul>

    <div class="rounded-3xl bg-white border border-marian-950/15">
        <form method="GET" class="flex flex-col gap-2 border-b p-4 sm:flex-row">
            <x-text-input name="q" :value="request('q')" placeholder="Cari nama, email, atau nomor identitas…" class="w-full sm:w-80" />
            <x-select name="status" class="w-full sm:w-44">
                <option value="">Semua status</option>
                <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
            </x-select>
            <x-secondary-button type="submit">Filter</x-secondary-button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-marian-950/10 text-sm">
                <thead class="bg-marian-50 text-left text-xs font-semibold uppercase text-marian-950/55">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">No. Identitas</th>
                        <th class="px-4 py-3">Unit / Prodi</th>
                        <th class="px-4 py-3">Kontak</th>
                        <th class="px-4 py-3">Transaksi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-marian-950/10">
                    @forelse ($peminjam as $p)
                        <tr>
                            <td class="px-4 py-3"><a href="{{ route('admin.peminjam.show', $p) }}" class="font-medium text-marian-900 hover:text-indigo-600">{{ $p->name }}</a></td>
                            <td class="px-4 py-3 font-mono">{{ $p->nomor_identitas ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $p->unit ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div>{{ $p->email }}</div>
                                <div class="text-xs text-marian-950/55">{{ $p->no_hp ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $p->peminjaman_count }}</td>
                            <td class="px-4 py-3"><x-admin.badge :value="$p->status" /></td>
                            <td class="space-x-3 whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.peminjam.show', $p) }}" class="font-medium text-marian-950/65 hover:text-marian-900">Detail</a>
                                <a href="{{ route('admin.peminjam.edit', $p) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Ubah</a>
                                <x-admin.hapus :action="route('admin.peminjam.destroy', $p)" :nama="'peminjam '.$p->name" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-marian-950/55">Tidak ada peminjam yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $peminjam->links() }}</div>
    </div>
</x-app-layout>
