@php
    // Baris awal: dari input lama (setelah gagal validasi) atau barang yang dipilih di katalog
    $barisAwal = old('items') ?: [['barang_id' => $barangDipilih ? (string) $barangDipilih : '', 'jumlah' => 1]];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold leading-tight text-gray-800">Ajukan Peminjaman</h1>
    </x-slot>

    @if ($terlambat)
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            Anda masih memiliki pinjaman yang <strong>terlambat</strong>. Kembalikan barang tersebut terlebih dahulu sebelum mengajukan peminjaman baru.
            <a href="{{ route('peminjam.pinjaman.index', ['status' => 'dipinjam']) }}" class="underline">Lihat pinjaman</a>
        </div>
    @endif

    <div class="max-w-3xl rounded-lg bg-white p-6 shadow"
         x-data="formAjuan(@js($barisAwal), @js(old('tanggal_pinjam', now()->toDateString())), @js(old('tanggal_rencana_kembali', now()->addDay()->toDateString())), {{ $maksUnit }})"
         x-init="cekStok()">
        <form method="POST" action="{{ route('peminjam.ajuan.store') }}" class="space-y-5">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="tanggal_pinjam" value="Tanggal Pinjam *" />
                    <x-text-input id="tanggal_pinjam" type="date" name="tanggal_pinjam" class="mt-1 block w-full"
                                  min="{{ now()->toDateString() }}" x-model="mulai" x-on:change="cekStok()" required />
                    <x-input-error :messages="$errors->get('tanggal_pinjam')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="tanggal_rencana_kembali" value="Tanggal Rencana Kembali *" />
                    <x-text-input id="tanggal_rencana_kembali" type="date" name="tanggal_rencana_kembali" class="mt-1 block w-full"
                                  x-bind:min="mulai" x-model="selesai" x-on:change="cekStok()" required />
                    <x-input-error :messages="$errors->get('tanggal_rencana_kembali')" class="mt-2" />
                </div>
            </div>
            <p class="-mt-3 text-xs text-gray-500">Lama pinjam maksimal {{ $maksHari }} hari. <span x-show="lama() !== null" x-text="'Lama: ' + lama() + ' hari'"></span></p>

            <div>
                <x-input-label value="Barang yang Dipinjam *" />
                <p class="text-xs text-gray-500">Maksimal {{ $maksUnit }} unit per ajuan. Sisa unit dihitung untuk rentang tanggal di atas.</p>

                <div class="mt-2 space-y-2">
                    <template x-for="(baris, i) in items" :key="i">
                        <div class="flex flex-wrap items-start gap-2 rounded-md border border-gray-200 p-2">
                            <select :name="`items[${i}][barang_id]`" x-model="baris.barang_id" @change="cekStok()"
                                    class="min-w-0 flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— Pilih barang —</option>
                                @foreach ($barang as $b)
                                    <option value="{{ $b->id }}">{{ $b->nama }}</option>
                                @endforeach
                            </select>
                            <input type="number" min="1" :name="`items[${i}][jumlah]`" x-model.number="baris.jumlah"
                                   class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" aria-label="Jumlah">
                            <button type="button" @click="hapus(i)" x-show="items.length > 1"
                                    class="rounded-md px-3 py-2 text-sm text-red-600 hover:bg-red-50">Hapus</button>
                            <div class="w-full text-xs" x-show="baris.barang_id && stok[baris.barang_id] !== undefined">
                                <span :class="stok[baris.barang_id] >= baris.jumlah ? 'text-green-700' : 'text-red-600'"
                                      x-text="'Sisa ' + stok[baris.barang_id] + ' unit pada tanggal tersebut'"></span>
                            </div>
                        </div>
                    </template>
                </div>

                @foreach ($errors->get('items*') as $pesan)
                    <x-input-error :messages="$pesan" class="mt-2" />
                @endforeach

                <div class="mt-2 flex items-center justify-between text-sm">
                    <button type="button" @click="tambah()" class="font-medium text-indigo-600 hover:text-indigo-800">+ Tambah barang</button>
                    <span :class="total() > maksUnit ? 'text-red-600 font-semibold' : 'text-gray-600'" x-text="'Total: ' + total() + ' / ' + maksUnit + ' unit'"></span>
                </div>
            </div>

            <div>
                <x-input-label for="tujuan" value="Tujuan Peminjaman *" />
                <textarea id="tujuan" name="tujuan" rows="3" minlength="5" maxlength="500" required
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          placeholder="Misal: presentasi seminar proposal di ruang 301">{{ old('tujuan') }}</textarea>
                <x-input-error :messages="$errors->get('tujuan')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button :disabled="$terlambat" class="disabled:opacity-50">Kirim Ajuan</x-primary-button>
                <a href="{{ route('peminjam.katalog.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Batal</a>
            </div>
        </form>
    </div>

    <script>
        // Komponen Alpine untuk baris barang dinamis + cek sisa unit ke server
        function formAjuan(items, mulai, selesai, maksUnit) {
            return {
                items, mulai, selesai, maksUnit, stok: {},
                tambah() { this.items.push({ barang_id: '', jumlah: 1 }); },
                hapus(i) { this.items.splice(i, 1); this.cekStok(); },
                total() { return this.items.reduce((t, b) => t + (parseInt(b.jumlah) || 0), 0); },
                lama() {
                    if (!this.mulai || !this.selesai) return null;
                    const hari = (new Date(this.selesai) - new Date(this.mulai)) / 86400000;
                    return hari >= 0 ? hari : null;
                },
                async cekStok() {
                    const ids = this.items.map(b => b.barang_id).filter(Boolean);
                    if (!ids.length || !this.mulai || !this.selesai || this.selesai < this.mulai) { this.stok = {}; return; }
                    const params = new URLSearchParams({ mulai: this.mulai, selesai: this.selesai });
                    ids.forEach(id => params.append('barang[]', id));
                    try {
                        const res = await fetch(`{{ route('peminjam.ketersediaan') }}?${params}`, { headers: { Accept: 'application/json' } });
                        if (res.ok) this.stok = await res.json();
                    } catch (e) { /* abaikan; server tetap memvalidasi saat kirim */ }
                },
            };
        }
    </script>
</x-app-layout>
