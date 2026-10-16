@use('App\Enums\StatusUnit')
@use('App\Enums\KondisiUnit')
@php $dipinjam = $unit->status === StatusUnit::Dipinjam; @endphp

<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-950/55">Data Barang</span></x-slot>

    <x-admin.judul :judul="'Ubah Unit '.$unit->kode_unit" :kembali="route('admin.barang.show', $unit->barang_id)" />

    <div class="max-w-2xl rounded-3xl bg-white p-6 border border-marian-950/15">
        @if ($dipinjam)
            <div class="mb-4 rounded-2xl border border-marian-200 bg-marian-50 p-3 text-sm text-marian-800">
                Unit ini sedang dipinjam. Kondisi dan status akan diperbarui saat proses pengembalian.
            </div>
        @endif

        <dl class="mb-4 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-marian-950/55">Kode Unit</dt><dd class="font-mono font-medium">{{ $unit->kode_unit }}</dd></div>
            <div><dt class="text-marian-950/55">Jenis Barang</dt><dd class="font-medium">{{ $unit->barang->nama }}</dd></div>
        </dl>

        <form method="POST" action="{{ route('admin.unit.update', $unit) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="lokasi_id" value="Lokasi *" />
                <x-select id="lokasi_id" name="lokasi_id" class="mt-1 block w-full" required>
                    @foreach ($lokasi as $l)
                        <option value="{{ $l->id }}" @selected(old('lokasi_id', $unit->lokasi_id) == $l->id)>{{ $l->nama }}</option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('lokasi_id')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="kondisi" value="Kondisi *" />
                    <x-select id="kondisi" name="kondisi" class="mt-1 block w-full" required :disabled="$dipinjam">
                        @foreach (KondisiUnit::cases() as $k)
                            <option value="{{ $k->value }}" @selected(old('kondisi', $unit->kondisi->value) === $k->value)>{{ $k->label() }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('kondisi')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="status" value="Status *" />
                    <x-select id="status" name="status" class="mt-1 block w-full" required :disabled="$dipinjam">
                        @if ($dipinjam)
                            <option value="dipinjam" selected>Dipinjam</option>
                        @endif
                        @foreach (StatusUnit::pilihanManual() as $s)
                            <option value="{{ $s->value }}" @selected(old('status', $unit->status->value) === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </x-select>
                    <p class="mt-1 text-xs text-marian-950/55">Rusak → perbaikan, hilang → hilang. Hanya kondisi baik yang boleh tersedia.</p>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="tanggal_perolehan" value="Tanggal Perolehan" />
                <x-text-input id="tanggal_perolehan" type="date" name="tanggal_perolehan" max="{{ now()->toDateString() }}" class="mt-1 block w-full sm:w-1/2"
                              :value="old('tanggal_perolehan', $unit->tanggal_perolehan?->toDateString())" />
                <x-input-error :messages="$errors->get('tanggal_perolehan')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button :disabled="$dipinjam" class="disabled:opacity-50">Simpan</x-primary-button>
                <a href="{{ route('admin.barang.show', $unit->barang_id) }}" class="text-sm text-marian-950/65 hover:text-marian-900">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
