@use('App\Enums\StatusUnit')
@use('App\Enums\KondisiUnit')
@php $dipinjam = $unit->status === StatusUnit::Dipinjam; @endphp

<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Data Barang</span></x-slot>

    <x-admin.judul :judul="'Ubah Unit '.$unit->kode_unit" :kembali="route('admin.barang.show', $unit->barang_id)" />

    {{-- Unit dalam perbaikan: satu klik untuk mengembalikannya ke kondisi baik & tersedia --}}
    @if ($unit->status === StatusUnit::Perbaikan)
        <div id="perbaikan" class="mb-4 max-w-2xl rounded-3xl bg-gold/20 p-5 ring-1 ring-gold">
            <h2 class="font-extrabold text-marian-900">Unit sedang dalam perbaikan</h2>
            <p class="mt-1 text-sm text-marian-700">Jika sudah diperbaiki, catat keterangannya. Kondisi menjadi <strong>Baik</strong> dan status <strong>Tersedia</strong>.</p>
            <form method="POST" action="{{ route('admin.unit.perbaikan-selesai', $unit) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start">
                @csrf
                <div class="flex-1">
                    <x-text-input name="keterangan" class="block w-full" required minlength="5" maxlength="500" placeholder="Mis. elemen pemanas diganti teknisi" aria-label="Keterangan perbaikan" />
                    <x-input-error :messages="$errors->get('keterangan')" class="mt-1" />
                </div>
                <x-primary-button>Perbaikan selesai</x-primary-button>
            </form>
        </div>
    @endif

    <div class="max-w-2xl rounded-3xl bg-white p-6 shadow-lembut ring-1 ring-marian-100">
        @if ($dipinjam)
            <div class="mb-4 rounded-2xl border border-marian-200 bg-marian-50 p-3 text-sm text-marian-800">
                Unit ini sedang dipinjam. Kondisi dan status akan diperbarui saat proses pengembalian.
            </div>
        @endif

        <dl class="mb-4 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-marian-500">Kode Unit</dt><dd class="font-mono font-medium">{{ $unit->kode_unit }}</dd></div>
            <div><dt class="text-marian-500">Jenis Barang</dt><dd class="font-medium">{{ $unit->barang->nama }}</dd></div>
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
                    <p class="mt-1 text-xs text-marian-500">Rusak → perbaikan, hilang → hilang. Hanya kondisi baik yang boleh tersedia.</p>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="keterangan" value="Keterangan perubahan kondisi/status" />
                <x-text-input id="keterangan" name="keterangan" class="mt-1 block w-full" :value="old('keterangan')" maxlength="500" placeholder="Opsional, mis. lensa retak saat dibersihkan" :disabled="$dipinjam" />
                <p class="mt-1 text-xs text-marian-500">Dicatat di riwayat kondisi bila kondisi atau status berubah.</p>
                <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tanggal_perolehan" value="Tanggal Perolehan" />
                <x-text-input id="tanggal_perolehan" type="date" name="tanggal_perolehan" max="{{ now()->toDateString() }}" class="mt-1 block w-full sm:w-1/2"
                              :value="old('tanggal_perolehan', $unit->tanggal_perolehan?->toDateString())" />
                <x-input-error :messages="$errors->get('tanggal_perolehan')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button :disabled="$dipinjam" class="disabled:opacity-50">Simpan</x-primary-button>
                <a href="{{ route('admin.barang.show', $unit->barang_id) }}" class="text-sm text-marian-600 hover:text-marian-900">Batal</a>
            </div>
        </form>
    </div>

    {{-- Riwayat kondisi unit (terbaru di atas) --}}
    <section class="mt-4 max-w-4xl rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100">
        <h2 class="border-b px-5 py-4 font-extrabold text-marian-900">Riwayat Kondisi</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-marian-100 text-sm">
                <thead class="bg-marian-50 text-left text-xs font-semibold uppercase text-marian-500">
                    <tr>
                        <th class="px-4 py-2">Waktu</th>
                        <th class="px-4 py-2">Jenis</th>
                        <th class="px-4 py-2">Perubahan</th>
                        <th class="px-4 py-2">Keterangan</th>
                        <th class="px-4 py-2">Dicatat oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-marian-50">
                    @forelse ($unit->riwayatKondisi as $r)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-2">{{ $r->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-2">
                                {{ $r->labelJenis() }}
                                @if ($r->peminjaman)
                                    <a href="{{ route('admin.peminjaman.show', $r->peminjaman) }}" class="block font-mono text-xs text-indigo-600 hover:underline">{{ $r->peminjaman->kode_peminjaman }}</a>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-2">
                                <x-admin.badge :value="$r->kondisi_lama" /> → <x-admin.badge :value="$r->kondisi_baru" />
                                <span class="mt-1 block text-xs text-marian-500">{{ $r->status_lama->label() }} → {{ $r->status_baru->label() }}</span>
                            </td>
                            <td class="px-4 py-2">{{ $r->keterangan ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-2">{{ $r->pencatat?->name ?? 'Sistem' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-marian-500">Belum ada perubahan kondisi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
