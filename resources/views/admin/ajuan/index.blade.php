<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Transaksi</span></x-slot>

    <x-admin.judul judul="Ajuan Peminjaman" />

    <div class="rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-marian-100 text-sm">
                <thead class="bg-marian-50 text-left text-xs font-semibold uppercase text-marian-500">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Peminjam</th>
                        <th class="px-4 py-3">Barang</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Diajukan</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-marian-50">
                    @forelse ($ajuan as $a)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-mono">{{ $a->kode_peminjaman }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $a->peminjam->name }}</div>
                                <div class="text-xs text-marian-500">{{ $a->peminjam->nomor_identitas }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $a->ringkasanBarang() }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $a->tanggal_pinjam->translatedFormat('d M') }} – {{ $a->tanggal_rencana_kembali->translatedFormat('d M Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-marian-500">{{ $a->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-link-button :href="route('admin.peminjaman.show', $a)" class="!px-3">Verifikasi</x-link-button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-marian-500">Tidak ada ajuan yang menunggu verifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $ajuan->links() }}</div>
    </div>
</x-app-layout>
