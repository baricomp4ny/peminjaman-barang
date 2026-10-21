@use('App\Enums\StatusPeminjaman')
<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Transaksi</span></x-slot>

    <x-admin.judul judul="Peminjaman" />

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        @foreach (['disetujui' => 'Siap diambil', 'dipinjam' => 'Sedang dipinjam', 'terlambat' => 'Terlambat'] as $nilai => $label)
            <a href="{{ route('admin.peminjaman.index', ['tab' => $nilai]) }}"
               class="rounded-full px-3 py-1 {{ $tab === $nilai ? 'bg-indigo-600 text-white' : 'bg-white text-marian-700 shadow-sm hover:bg-marian-50' }}">
                {{ $label }} <span class="ms-1 opacity-75">({{ $jumlah[$nilai] }})</span>
            </a>
        @endforeach
    </div>

    <div class="rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-marian-100 text-sm">
                <thead class="bg-marian-50 text-left text-xs font-semibold uppercase text-marian-500">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Peminjam</th>
                        <th class="px-4 py-3">Barang</th>
                        <th class="px-4 py-3">{{ $tab === 'disetujui' ? 'Ambil' : 'Rencana kembali' }}</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-marian-50">
                    @forelse ($peminjaman as $p)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-mono"><a href="{{ route('admin.peminjaman.show', $p) }}" class="hover:text-indigo-600">{{ $p->kode_peminjaman }}</a></td>
                            <td class="px-4 py-3">{{ $p->peminjam->name }}</td>
                            <td class="px-4 py-3">{{ $p->ringkasanBarang() }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($tab === 'disetujui')
                                    {{ $p->tanggal_pinjam->translatedFormat('d M') }} – {{ $service->batasAmbil($p)->translatedFormat('d M Y') }}
                                @else
                                    {{ $p->tanggal_rencana_kembali->translatedFormat('d M Y') }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($p->isTerlambat())
                                    <span class="rounded-full bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">Terlambat {{ $p->hariTerlambatBerjalan() }} hari</span>
                                @else
                                    <x-admin.badge :value="$p->status" />
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @if ($p->status === StatusPeminjaman::Disetujui)
                                    <x-link-button :href="route('admin.peminjaman.serah', $p)" class="!px-3">Serah-terima</x-link-button>
                                @else
                                    @if ($p->isTerlambat())
                                        <x-admin.tombol-wa :peminjaman="$p" class="me-1" />
                                    @endif
                                    <x-link-button :href="route('admin.pengembalian.create', $p)" class="!px-3">Catat Pengembalian</x-link-button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-marian-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $peminjaman->links() }}</div>
    </div>
</x-app-layout>
