<x-app-layout>
    <x-slot name="header"><span class="text-sm text-gray-500">Transaksi</span></x-slot>

    <x-admin.judul judul="Pengembalian" />

    <div class="rounded-lg bg-white shadow">
        <form method="GET" class="flex gap-2 border-b p-4">
            <x-text-input name="q" :value="request('q')" placeholder="Cari kode peminjaman / nama peminjam…" class="w-full sm:w-80" />
            <x-secondary-button type="submit">Cari</x-secondary-button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Peminjam</th>
                        <th class="px-4 py-3">Barang</th>
                        <th class="px-4 py-3">Belum kembali</th>
                        <th class="px-4 py-3">Rencana kembali</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($peminjaman as $p)
                        <tr class="{{ $p->isTerlambat() ? 'bg-red-50/50' : '' }}">
                            <td class="whitespace-nowrap px-4 py-3 font-mono">{{ $p->kode_peminjaman }}</td>
                            <td class="px-4 py-3">{{ $p->peminjam->name }}</td>
                            <td class="px-4 py-3">{{ $p->ringkasanBarang() }}</td>
                            <td class="px-4 py-3">{{ $p->peminjamanUnit->whereNull('dikembalikan_pada')->count() }} unit</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                {{ $p->tanggal_rencana_kembali->translatedFormat('d M Y') }}
                                @if ($p->isTerlambat())
                                    <span class="ms-1 rounded-full bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">+{{ $p->hariTerlambatBerjalan() }} hari</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-link-button :href="route('admin.pengembalian.create', $p)" class="!px-3">Catat</x-link-button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada barang yang sedang dipinjam.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $peminjaman->links() }}</div>
    </div>
</x-app-layout>
