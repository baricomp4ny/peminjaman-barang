<x-app-layout>
    <x-slot name="header"><span class="text-sm text-gray-500">Data Barang</span></x-slot>

    <x-admin.judul judul="Tambah Unit Barang"
                   :kembali="$barangDipilih ? route('admin.barang.show', $barangDipilih) : route('admin.unit.index')" />

    <div class="max-w-2xl rounded-lg bg-white p-6 shadow"
         x-data="{ barang: '{{ old('barang_id', $barangDipilih) }}', kode: @js($kodeBerikutnya), jumlah: {{ (int) old('jumlah', 1) }} }">
        <form method="POST" action="{{ route('admin.unit.store') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="barang_id" value="Jenis Barang *" />
                <x-select id="barang_id" name="barang_id" class="mt-1 block w-full" x-model="barang" required>
                    <option value="">— Pilih jenis barang —</option>
                    @foreach ($barang as $b)
                        <option value="{{ $b->id }}">{{ $b->nama }} ({{ $b->kode }})</option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('barang_id')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="jumlah" value="Jumlah Unit * (1–50)" />
                    <x-text-input id="jumlah" type="number" name="jumlah" min="1" max="50" class="mt-1 block w-full" x-model.number="jumlah" required />
                    <x-input-error :messages="$errors->get('jumlah')" class="mt-2" />
                </div>
                <div>
                    <x-input-label value="Kode Unit (otomatis)" />
                    <div class="mt-1 rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-2 font-mono text-sm text-gray-700">
                        <span x-show="barang && kode[barang]" x-text="kode[barang] + (jumlah > 1 ? ' s/d …' : '')"></span>
                        <span x-show="!barang" class="text-gray-400">Pilih jenis barang</span>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="lokasi_id" value="Lokasi *" />
                    <x-select id="lokasi_id" name="lokasi_id" class="mt-1 block w-full" required>
                        <option value="">— Pilih lokasi —</option>
                        @foreach ($lokasi as $l)
                            <option value="{{ $l->id }}" @selected(old('lokasi_id') == $l->id)>{{ $l->nama }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('lokasi_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="kondisi" value="Kondisi *" />
                    <x-select id="kondisi" name="kondisi" class="mt-1 block w-full" required>
                        @foreach (['baik', 'rusak', 'hilang'] as $k)
                            <option value="{{ $k }}" @selected(old('kondisi', 'baik') === $k)>{{ ucfirst($k) }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('kondisi')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="tanggal_perolehan" value="Tanggal Perolehan" />
                <x-text-input id="tanggal_perolehan" type="date" name="tanggal_perolehan" max="{{ now()->toDateString() }}" class="mt-1 block w-full sm:w-1/2" :value="old('tanggal_perolehan')" />
                <x-input-error :messages="$errors->get('tanggal_perolehan')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>Simpan</x-primary-button>
                <a href="{{ $barangDipilih ? route('admin.barang.show', $barangDipilih) : route('admin.unit.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
