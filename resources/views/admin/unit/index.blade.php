@use('App\Enums\StatusUnit')
<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Data Barang</span></x-slot>

    <x-admin.judul judul="Unit Barang">
        <x-link-button variant="secondary" :href="route('admin.unit.label', request()->only(['barang_id', 'lokasi_id', 'status']))">Cetak Label (sesuai filter)</x-link-button>
        <x-link-button :href="route('admin.unit.create')">+ Tambah Unit</x-link-button>
    </x-admin.judul>

    <div class="rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100" x-data="pilihBanyak(@js($unit->pluck('id')))">
        <form method="GET" class="grid gap-2 border-b p-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-text-input name="q" :value="request('q')" placeholder="Cari kode unit…" />
            <x-select name="barang_id">
                <option value="">Semua jenis barang</option>
                @foreach ($barang as $b)
                    <option value="{{ $b->id }}" @selected(request('barang_id') == $b->id)>{{ $b->nama }} ({{ $b->kode }})</option>
                @endforeach
            </x-select>
            <x-select name="lokasi_id">
                <option value="">Semua lokasi</option>
                @foreach ($lokasi as $l)
                    <option value="{{ $l->id }}" @selected(request('lokasi_id') == $l->id)>{{ $l->nama }}</option>
                @endforeach
            </x-select>
            <x-select name="status">
                <option value="">Semua status</option>
                @foreach (StatusUnit::cases() as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </x-select>
            <x-secondary-button type="submit" class="justify-center">Filter</x-secondary-button>
        </form>

        {{-- Checkbox tabel dipakai bersama: Cetak label (form GET, atribut form="form-label") & Hapus terpilih --}}
        <form id="form-label" method="GET" action="{{ route('admin.unit.label') }}" target="_blank" class="hidden"></form>
        <x-admin.bilah-pilih :aksi="route('admin.unit.hapus-terpilih')" sebutan="unit">
            <button type="submit" form="form-label" class="rounded-full bg-white px-4 py-1.5 text-xs font-bold text-marian-800 ring-1 ring-marian-200 hover:bg-marian-50">Cetak label terpilih</button>
        </x-admin.bilah-pilih>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-marian-100 text-sm">
                <thead class="bg-marian-50 text-left text-xs font-semibold uppercase text-marian-500">
                    <tr>
                        <th class="w-8 px-4 py-3"><input type="checkbox" class="rounded border-marian-300 text-marian-800" aria-label="Pilih semua di halaman ini" :checked="semuaDipilih" :indeterminate="sebagianDipilih" @change="pilihSemua($event.target.checked)"></th>
                        <th class="px-4 py-3">Kode Unit</th>
                        <th class="px-4 py-3">Jenis Barang</th>
                        <th class="px-4 py-3">Lokasi</th>
                        <th class="px-4 py-3">Kondisi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Tgl. Perolehan</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-marian-50">
                    @forelse ($unit as $u)
                        <tr>
                            <td class="px-4 py-3"><input type="checkbox" form="form-label" name="unit[]" value="{{ $u->id }}" x-model="dipilih" class="rounded border-marian-300 text-marian-800" aria-label="Pilih {{ $u->kode_unit }}"></td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono font-medium">{{ $u->kode_unit }}</td>
                            <td class="px-4 py-3"><a href="{{ route('admin.barang.show', $u->barang_id) }}" class="hover:text-indigo-600">{{ $u->barang->nama }}</a></td>
                            <td class="px-4 py-3">{{ $u->lokasi->nama }}</td>
                            <td class="px-4 py-3"><x-admin.badge :value="$u->kondisi" /></td>
                            <td class="px-4 py-3"><x-admin.badge :value="$u->status" /></td>
                            <td class="px-4 py-3">{{ $u->tanggal_perolehan?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="space-x-3 whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.unit.qr', [$u, 'unduh' => 1]) }}" class="font-medium text-marian-600 hover:text-marian-900">QR</a>
                                @if ($u->status === \App\Enums\StatusUnit::Perbaikan)
                                    <a href="{{ route('admin.unit.edit', $u) }}#perbaikan" class="font-semibold text-marian-900 hover:underline">Perbaikan selesai</a>
                                @endif
                                <a href="{{ route('admin.unit.edit', $u) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Ubah</a>
                                <x-admin.hapus :action="route('admin.unit.destroy', $u)" :nama="'unit '.$u->kode_unit" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-marian-500">Tidak ada unit yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $unit->links() }}</div>
    </div>
</x-app-layout>
