@php $p = $peminjaman; @endphp

<x-app-layout>
    <x-slot name="header"><span class="text-sm text-gray-500">Transaksi</span></x-slot>

    <x-admin.judul :judul="'Pengembalian '.$p->kode_peminjaman" :kembali="route('admin.peminjaman.show', $p)" />

    <div class="max-w-3xl rounded-lg bg-white p-6 shadow">
        <dl class="grid gap-3 text-sm sm:grid-cols-3">
            <div><dt class="text-gray-500">Peminjam</dt><dd class="font-medium">{{ $p->peminjam->name }}</dd></div>
            <div><dt class="text-gray-500">Rencana kembali</dt><dd class="font-medium">{{ $p->tanggal_rencana_kembali->translatedFormat('d M Y') }}</dd></div>
            <div>
                <dt class="text-gray-500">Jika kembali hari ini</dt>
                <dd class="font-medium {{ $dendaHariIni['hari'] > 0 ? 'text-red-600' : 'text-green-700' }}">
                    {{ $dendaHariIni['hari'] > 0 ? "Terlambat {$dendaHariIni['hari']} hari · Rp".number_format($dendaHariIni['denda'], 0, ',', '.').'/unit' : 'Tepat waktu' }}
                </dd>
            </div>
        </dl>

        <form method="POST" action="{{ route('admin.pengembalian.store', $p) }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <x-input-label for="tanggal_kembali" value="Tanggal Kembali *" />
                <x-text-input id="tanggal_kembali" type="date" name="tanggal_kembali" class="mt-1 block w-full sm:w-1/2"
                              :value="old('tanggal_kembali', now()->toDateString())"
                              min="{{ $p->diserahkan_pada->toDateString() }}" max="{{ now()->toDateString() }}" required />
                <p class="mt-1 text-xs text-gray-500">Ubah hanya bila pencatatan dilakukan setelah barang sebenarnya kembali.</p>
                <x-input-error :messages="$errors->get('tanggal_kembali')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="Unit yang dikembalikan (boleh sebagian)" />
                <div class="mt-2 divide-y rounded-md border">
                    @foreach ($belumKembali as $u)
                        @php $id = $u->unit_barang_id; @endphp
                        <div class="grid items-center gap-2 p-3 sm:grid-cols-12" x-data="{ pilih: {{ old("unit.$id.pilih", '1') ? 'true' : 'false' }} }">
                            <label class="flex items-center gap-2 sm:col-span-4">
                                <input type="hidden" name="unit[{{ $id }}][pilih]" value="0">
                                <input type="checkbox" name="unit[{{ $id }}][pilih]" value="1" x-model="pilih" class="rounded border-gray-300 text-indigo-600">
                                <span class="font-mono text-sm">{{ $u->unitBarang->kode_unit }}</span>
                                <span class="text-xs text-gray-500">{{ $u->unitBarang->barang->nama }}</span>
                            </label>
                            <div class="sm:col-span-3">
                                <x-select name="unit[{{ $id }}][kondisi]" class="block w-full text-sm" x-bind:disabled="!pilih" aria-label="Kondisi {{ $u->unitBarang->kode_unit }}">
                                    @foreach (['baik' => 'Baik', 'rusak' => 'Rusak', 'hilang' => 'Hilang'] as $nilai => $label)
                                        <option value="{{ $nilai }}" @selected(old("unit.$id.kondisi", 'baik') === $nilai)>{{ $label }}</option>
                                    @endforeach
                                </x-select>
                            </div>
                            <div class="sm:col-span-5">
                                <x-text-input name="unit[{{ $id }}][catatan]" class="block w-full text-sm" placeholder="Catatan (opsional)"
                                              :value="old('unit.'.$id.'.catatan')" maxlength="255" x-bind:disabled="!pilih" />
                            </div>
                            <x-input-error :messages="$errors->get('unit.'.$id.'.kondisi')" class="sm:col-span-12" />
                        </div>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-gray-500">Rusak → unit masuk perbaikan. Hilang → unit ditandai hilang. Keduanya tidak bisa dipinjam lagi.</p>
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>Simpan Pengembalian</x-primary-button>
                <a href="{{ route('admin.peminjaman.show', $p) }}" class="text-sm text-gray-600 hover:text-gray-800">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
