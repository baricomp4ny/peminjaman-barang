<x-app-layout>
    <x-slot name="header"><span class="text-sm text-gray-500">Transaksi</span></x-slot>

    <x-admin.judul judul="Ajuan Peminjaman" />

    <div class="rounded-lg bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Peminjam</th>
                        <th class="px-4 py-3">Barang</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Diajukan</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($ajuan as $a)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-mono">{{ $a->kode_peminjaman }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $a->peminjam->name }}</div>
                                <div class="text-xs text-gray-500">{{ $a->peminjam->nomor_identitas }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $a->ringkasanBarang() }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $a->tanggal_pinjam->translatedFormat('d M') }} – {{ $a->tanggal_rencana_kembali->translatedFormat('d M Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ $a->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-link-button :href="route('admin.peminjaman.show', $a)" class="!px-3">Verifikasi</x-link-button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada ajuan yang menunggu verifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $ajuan->links() }}</div>
    </div>
</x-app-layout>
