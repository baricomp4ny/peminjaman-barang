<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Data Peminjam</span></x-slot>

    <x-admin.judul :judul="$peminjam->name" :kembali="route('admin.peminjam.index')">
        <x-link-button :href="route('admin.peminjam.edit', $peminjam)">Ubah</x-link-button>
    </x-admin.judul>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl bg-white p-6 shadow-lembut ring-1 ring-marian-100">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-marian-500">Status</dt><dd><x-admin.badge :value="$peminjam->status" /></dd></div>
                <div><dt class="text-marian-500">Nomor Identitas</dt><dd class="font-mono font-medium">{{ $peminjam->nomor_identitas ?? '—' }}</dd></div>
                <div><dt class="text-marian-500">Unit / Prodi</dt><dd>{{ $peminjam->unit ?? '—' }}</dd></div>
                <div><dt class="text-marian-500">Email</dt><dd>{{ $peminjam->email }}</dd></div>
                <div><dt class="text-marian-500">No. HP</dt><dd>{{ $peminjam->no_hp ?? '—' }}</dd></div>
                <div><dt class="text-marian-500">Terdaftar</dt><dd>{{ $peminjam->created_at->translatedFormat('d F Y') }}</dd></div>
            </dl>
        </div>

        <div class="rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100 lg:col-span-2">
            <div class="border-b px-4 py-3 font-semibold text-marian-900">10 Transaksi Terakhir</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-marian-100 text-sm">
                    <thead class="bg-marian-50 text-left text-xs font-semibold uppercase text-marian-500">
                        <tr>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Barang</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-marian-50">
                        @forelse ($riwayat as $r)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 font-mono">{{ $r->kode_peminjaman }}</td>
                                <td class="px-4 py-3">
                                    {{ $r->detail->map(fn ($d) => $d->barang->nama.' ×'.$d->jumlah)->join(', ') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{ $r->tanggal_pinjam->translatedFormat('d M') }} – {{ $r->tanggal_rencana_kembali->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-4 py-3"><x-admin.badge :value="$r->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-marian-500">Belum pernah mengajukan peminjaman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
