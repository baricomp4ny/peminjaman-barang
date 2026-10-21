<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Transaksi</span></x-slot>

    {{-- Unduhan mengikuti filter yang sedang dipakai (pengganti Laporan Transaksi) --}}
    @php $filter = request()->only(['q', 'status', 'dari', 'sampai']); @endphp
    <x-admin.judul judul="Riwayat Peminjaman">
        <x-link-button variant="secondary" :href="route('admin.riwayat.index', $filter + ['format' => 'pdf'])">Unduh PDF</x-link-button>
        <x-link-button :href="route('admin.riwayat.index', $filter + ['format' => 'excel'])">Unduh Excel</x-link-button>
    </x-admin.judul>

    <div class="rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100">
        <form method="GET" class="grid gap-2 border-b p-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-text-input name="q" :value="request('q')" placeholder="Kode / nama / NIM…" />
            <x-select name="status">
                <option value="">Semua status</option>
                @foreach ($daftarStatus as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </x-select>
            <x-text-input type="date" name="dari" :value="request('dari')" aria-label="Dari tanggal" />
            <x-text-input type="date" name="sampai" :value="request('sampai')" aria-label="Sampai tanggal" />
            <x-secondary-button type="submit" class="justify-center">Filter</x-secondary-button>
            @if ($errors->any())
                <div class="sm:col-span-2 lg:col-span-5"><x-input-error :messages="$errors->all()" /></div>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-marian-100 text-sm">
                <thead class="bg-marian-50 text-left text-xs font-semibold uppercase text-marian-500">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Peminjam</th>
                        <th class="px-4 py-3">Barang</th>
                        <th class="px-4 py-3">Tanggal pinjam</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Terlambat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-marian-50">
                    @forelse ($riwayat as $r)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-mono"><a href="{{ route('admin.peminjaman.show', $r) }}" class="text-indigo-600 hover:underline">{{ $r->kode_peminjaman }}</a></td>
                            <td class="px-4 py-3">{{ $r->peminjam->name }}</td>
                            <td class="px-4 py-3">{{ $r->ringkasanBarang() }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $r->tanggal_pinjam->translatedFormat('d M Y') }} – {{ $r->tanggal_rencana_kembali->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <x-admin.badge :value="$r->status" />
                                @if ($r->isTerlambat()) <span class="ms-1 rounded-full bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">Terlambat</span> @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @php $h = $r->peminjamanUnit->max('hari_terlambat'); @endphp
                                {{ $h ? $h.' hari' : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-marian-500">Tidak ada transaksi yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $riwayat->links() }}</div>
    </div>
</x-app-layout>
