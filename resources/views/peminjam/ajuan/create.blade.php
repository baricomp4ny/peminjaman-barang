@php
    // Baris awal: dari input lama (setelah gagal validasi) atau barang yang dipilih di katalog
    $barisAwal = old('items') ?: [['barang_id' => $barangDipilih ? (string) $barangDipilih : '', 'jumlah' => 1]];
    $namaBarang = $barang->pluck('nama', 'id');
@endphp

<x-app-layout>
    {{-- Banner judul --}}
    <section class="relative overflow-hidden rounded-5xl bg-gradient-to-br from-marian-950 via-marian-800 to-marian-500 px-6 py-8 text-white sm:px-10">
        <div aria-hidden="true" class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-6">
            <div class="max-w-2xl">
                <span class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider ring-1 ring-white/25">Tanpa login</span>
                <h1 class="mt-3 font-extrabold [font-size:clamp(1.6rem,3.4vw,2.5rem)]">Ajukan Peminjaman Alat</h1>
                <p class="mt-2 text-sm text-marian-100">Isi identitas, pilih alat dan tanggal. Kode peminjaman beserta QR pengambilan akan tampil setelah dikirim dan dikirim juga ke email Anda.</p>
            </div>
            <x-ikon-alat nama="labu" class="hidden h-24 w-24 text-white/30 sm:block" />
        </div>
    </section>

    <div class="mt-6 grid items-start gap-6 lg:[grid-template-columns:minmax(0,1fr)_20rem]"
         x-data="formAjuan(@js($barisAwal), @js(old('tanggal_pinjam', now()->toDateString())), @js(old('tanggal_rencana_kembali', now()->addDay()->toDateString())), {{ $maksUnit }}, @js($namaBarang))"
         x-init="cekStok()">

        <form method="POST" action="{{ route('peminjam.ajuan.store') }}" class="space-y-6 rounded-5xl bg-white p-5 shadow-lembut ring-1 ring-marian-100 sm:p-8">
            @csrf

            {{-- 1. Identitas (tanpa login). NIM dipakai untuk mencocokkan riwayat peminjaman. --}}
            <fieldset>
                <legend class="flex items-center gap-3 text-base font-extrabold text-marian-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-marian-800 text-sm text-white">1</span> Identitas Peminjam
                </legend>
                <div class="mt-4 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,15rem),1fr))]">
                    <div>
                        <x-input-label for="name" value="Nama Lengkap *" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required maxlength="255" autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="nomor_identitas" value="Nomor Identitas (NIM/NIP) *" />
                        <x-text-input id="nomor_identitas" name="nomor_identitas" class="mt-1 block w-full" :value="old('nomor_identitas')" required maxlength="50" placeholder="J0301…" />
                        <x-input-error :messages="$errors->get('nomor_identitas')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email *" />
                        <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" :value="old('email')" required maxlength="255" autocomplete="email" />
                        <p class="mt-1 text-xs text-marian-500">Kode peminjaman dan kabar persetujuan dikirim ke email ini.</p>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="no_hp" value="No. HP / WhatsApp *" />
                        <x-text-input id="no_hp" name="no_hp" class="mt-1 block w-full" :value="old('no_hp')" required maxlength="20" placeholder="08xxxxxxxxxx" autocomplete="tel" />
                        <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
                    </div>
                    <div class="col-span-full">
                        <x-input-label for="unit" value="Program Studi / Unit" />
                        <x-text-input id="unit" name="unit" class="mt-1 block w-full" :value="old('unit')" maxlength="100" placeholder="Mis. Teknologi Industri Benih" />
                        <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                    </div>
                </div>
                {{-- Jebakan bot: tidak terlihat oleh manusia, harus dibiarkan kosong --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>
            </fieldset>

            {{-- 2. Jadwal --}}
            <fieldset class="border-t border-marian-100 pt-6">
                <legend class="flex items-center gap-3 text-base font-extrabold text-marian-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-marian-800 text-sm text-white">2</span> Jadwal Peminjaman
                </legend>
                <div class="mt-4 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,13rem),1fr))]">
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
                <p class="mt-2 text-xs text-marian-500">Lama pinjam maksimal {{ $maksHari }} hari.
                    <span x-show="lama() !== null" class="font-semibold" :class="lama() > {{ $maksHari }} ? 'text-red-600' : 'text-marian-800'" x-text="'Lama: ' + lama() + ' hari'"></span>
                </p>
            </fieldset>

            {{-- 3. Alat --}}
            <fieldset class="border-t border-marian-100 pt-6">
                <legend class="flex items-center gap-3 text-base font-extrabold text-marian-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-marian-800 text-sm text-white">3</span> Alat yang Dipinjam
                </legend>
                <p class="mt-2 text-xs text-marian-500">Maksimal {{ $maksUnit }} unit per ajuan. Sisa unit dihitung untuk rentang tanggal di atas.</p>

                <div class="mt-3 space-y-3">
                    <template x-for="(baris, i) in items" :key="i">
                        <div class="rounded-3xl bg-marian-50 p-3 ring-1 ring-marian-100">
                            <div class="flex flex-wrap items-center gap-2">
                                <select :name="`items[${i}][barang_id]`" x-model="baris.barang_id" @change="cekStok()" aria-label="Pilih alat"
                                        class="min-w-[12rem] flex-1 rounded-xl border-marian-200 bg-white text-marian-950 shadow-sm focus:border-marian-500 focus:ring-marian-500">
                                    <option value="">— Pilih alat —</option>
                                    @foreach ($barang as $b)
                                        <option value="{{ $b->id }}">{{ $b->nama }}</option>
                                    @endforeach
                                </select>
                                {{-- Jumlah dengan tombol - / + --}}
                                <div class="flex items-center rounded-full bg-white ring-1 ring-marian-200">
                                    <button type="button" @click="baris.jumlah = Math.max(1, (parseInt(baris.jumlah) || 1) - 1)" class="h-10 w-10 rounded-full text-lg font-bold text-marian-700 hover:bg-marian-50" aria-label="Kurangi">−</button>
                                    <input type="number" min="1" :name="`items[${i}][jumlah]`" x-model.number="baris.jumlah" aria-label="Jumlah"
                                           class="w-12 border-0 bg-transparent p-0 text-center font-bold text-marian-900 focus:ring-0">
                                    <button type="button" @click="baris.jumlah = (parseInt(baris.jumlah) || 0) + 1" class="h-10 w-10 rounded-full text-lg font-bold text-marian-700 hover:bg-marian-50" aria-label="Tambah">+</button>
                                </div>
                                <button type="button" @click="hapus(i)" x-show="items.length > 1"
                                        class="rounded-full px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Hapus</button>
                            </div>
                            <div class="mt-2 px-1 text-xs" x-show="baris.barang_id && stok[baris.barang_id] !== undefined">
                                <span class="font-semibold" :class="stok[baris.barang_id] >= baris.jumlah ? 'text-marian-700' : 'text-red-600'"
                                      x-text="'Sisa ' + stok[baris.barang_id] + ' unit pada tanggal tersebut'"></span>
                            </div>
                        </div>
                    </template>
                </div>

                @foreach ($errors->get('items*') as $pesan)
                    <x-input-error :messages="$pesan" class="mt-2" />
                @endforeach

                <button type="button" @click="tambah()" class="mt-3 inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-sm font-bold text-marian-700 ring-1 ring-marian-200 hover:bg-marian-50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
                    Tambah alat
                </button>
            </fieldset>

            {{-- 4. Tujuan --}}
            <fieldset class="border-t border-marian-100 pt-6">
                <legend class="flex items-center gap-3 text-base font-extrabold text-marian-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-marian-800 text-sm text-white">4</span> Tujuan Peminjaman
                </legend>
                <textarea id="tujuan" name="tujuan" rows="3" minlength="5" maxlength="500" required aria-label="Tujuan peminjaman"
                          class="mt-4 block w-full rounded-2xl border-marian-200 text-marian-950 shadow-sm placeholder:text-marian-300 focus:border-marian-500 focus:ring-marian-500"
                          placeholder="Mis. Inisiasi eksplan anggrek untuk praktikum Kultur Jaringan Tanaman">{{ old('tujuan') }}</textarea>
                <x-input-error :messages="$errors->get('tujuan')" class="mt-2" />
            </fieldset>

            {{-- 5. Syarat peminjaman (teks diatur admin) + persetujuan wajib --}}
            <fieldset class="border-t border-marian-100 pt-6">
                <legend class="flex items-center gap-3 text-base font-extrabold text-marian-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-marian-800 text-sm text-white">5</span> Syarat Peminjaman
                </legend>
                <div class="mt-4 max-h-48 overflow-y-auto whitespace-pre-line rounded-2xl bg-marian-50 p-4 text-sm leading-relaxed text-marian-800 ring-1 ring-marian-100">{{ $syarat }}</div>
                <label class="mt-3 flex items-start gap-3 text-sm font-semibold text-marian-900">
                    <input type="checkbox" name="setuju_syarat" value="1" required @checked(old('setuju_syarat'))
                           class="mt-0.5 h-5 w-5 shrink-0 rounded border-marian-300 text-marian-800 focus:ring-marian-500">
                    Saya menyetujui syarat peminjaman dan bertanggung jawab atas barang yang dipinjam
                </label>
                <x-input-error :messages="$errors->get('setuju_syarat')" class="mt-2" />
            </fieldset>

            <div class="flex flex-wrap items-center gap-3 border-t border-marian-100 pt-6">
                <x-primary-button class="px-7 py-3">
                    Kirim Ajuan
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </x-primary-button>
                <a href="{{ route('peminjam.katalog.index') }}" class="text-sm font-semibold text-marian-600 hover:text-marian-900">Batal</a>
            </div>
        </form>

        {{-- Ringkasan (menempel saat digulir di layar lebar) --}}
        <aside class="lg:sticky lg:top-28">
            <div class="rounded-5xl bg-gradient-to-br from-marian-900 to-marian-600 p-6 text-white shadow-lembut">
                <h2 class="text-sm font-bold uppercase tracking-wider text-marian-100">Ringkasan</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-marian-200">Lama pinjam</dt><dd class="font-bold" x-text="lama() !== null ? lama() + ' hari' : '—'"></dd></div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-marian-200">Total unit</dt>
                        <dd class="font-bold" :class="total() > maksUnit && 'rounded-full bg-white px-2 text-red-600'" x-text="total() + ' / ' + maksUnit"></dd>
                    </div>
                </dl>
                <ul class="mt-4 space-y-1.5 border-t border-white/15 pt-4 text-sm">
                    <template x-for="b in items.filter(b => b.barang_id)" :key="b.barang_id">
                        <li class="flex justify-between gap-3"><span class="truncate" x-text="nama[b.barang_id]"></span><span class="font-bold" x-text="'×' + b.jumlah"></span></li>
                    </template>
                    <li x-show="!items.some(b => b.barang_id)" class="text-marian-200">Belum ada alat dipilih.</li>
                </ul>
                {{-- Info pengambilan digabung ke kartu ringkasan (satu kartu saja) --}}
                <div class="mt-4 border-t border-white/15 pt-4 text-sm font-bold text-marian-100">Saat mengambil alat</div>
                <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-marian-100">
                    <li>Tunjukkan QR kode peminjaman (layar atau email).</li>
                    <li>Bawa kartu identitas asli (KTM/KTP).</li>
                    <li>Alat dipakai di lingkungan laboratorium.</li>
                </ul>
            </div>
        </aside>
    </div>

    <script>
        // Komponen Alpine untuk baris alat dinamis + cek sisa unit ke server
        function formAjuan(items, mulai, selesai, maksUnit, nama) {
            return {
                items, mulai, selesai, maksUnit, nama, stok: {},
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
