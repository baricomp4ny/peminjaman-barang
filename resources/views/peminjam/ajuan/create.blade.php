@php
    // Baris awal: dari input lama (setelah gagal validasi) atau barang yang dipilih di katalog
    $barisAwal = old('items') ?: [['barang_id' => $barangDipilih ? (string) $barangDipilih : '', 'jumlah' => 1]];
    $namaBarang = $barang->pluck('nama', 'id');
@endphp

<x-app-layout>
    {{-- Judul formulir --}}
    <section class="panel flex flex-wrap items-end justify-between gap-6 border-l-4 border-l-marian-800 px-6 py-7 sm:px-10">
        <div class="max-w-2xl">
            <div class="label-bagian">Formulir / Tanpa login</div>
            <h1 class="mt-3 font-semibold tracking-tight [font-size:clamp(1.6rem,3.4vw,2.3rem)]">Ajukan Peminjaman Alat</h1>
            <p class="mt-2 text-sm text-marian-950/70">Isi identitas, pilih alat dan tanggal. Kode peminjaman beserta QR pengambilan akan tampil setelah dikirim dan dikirim juga ke email Anda.</p>
        </div>
        <x-ikon-alat nama="labu" class="hidden h-16 w-16 text-marian-800/40 sm:block" />
    </section>

    <div class="mt-6 grid items-start gap-6 lg:[grid-template-columns:minmax(0,1fr)_20rem]"
         x-data="formAjuan(@js($barisAwal), @js(old('tanggal_pinjam', now()->toDateString())), @js(old('tanggal_rencana_kembali', now()->addDay()->toDateString())), {{ $maksUnit }}, @js($namaBarang))"
         x-init="cekStok()">

        <form method="POST" action="{{ route('peminjam.ajuan.store') }}" class="panel space-y-6 p-5 sm:p-8">
            @csrf

            {{-- 1. Identitas (tanpa login). NIM dipakai untuk mencocokkan riwayat peminjaman. --}}
            <fieldset>
                <legend class="flex items-center gap-3 text-base font-semibold">
                    <span class="font-mono text-sm text-marian-800">01 /</span> Identitas Peminjam
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
                        <p class="mt-1 text-xs text-marian-950/55">Kode peminjaman dan kabar persetujuan dikirim ke email ini.</p>
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
            <fieldset class="border-t border-marian-950/15 pt-6">
                <legend class="flex items-center gap-3 text-base font-semibold">
                    <span class="font-mono text-sm text-marian-800">02 /</span> Jadwal Peminjaman
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
                <p class="mt-2 text-xs text-marian-950/55">Lama pinjam maksimal {{ $maksHari }} hari.
                    <span x-show="lama() !== null" class="font-semibold" :class="lama() > {{ $maksHari }} ? 'text-red-600' : 'text-marian-800'" x-text="'Lama: ' + lama() + ' hari'"></span>
                </p>
            </fieldset>

            {{-- 3. Alat --}}
            <fieldset class="border-t border-marian-950/15 pt-6">
                <legend class="flex items-center gap-3 text-base font-semibold">
                    <span class="font-mono text-sm text-marian-800">03 /</span> Alat yang Dipinjam
                </legend>
                <p class="mt-2 text-xs text-marian-950/55">Maksimal {{ $maksUnit }} unit per ajuan. Sisa unit dihitung untuk rentang tanggal di atas.</p>

                <div class="mt-3 space-y-3">
                    <template x-for="(baris, i) in items" :key="i">
                        <div class="border border-marian-950/15 bg-marian-50/40 p-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <select :name="`items[${i}][barang_id]`" x-model="baris.barang_id" @change="cekStok()" aria-label="Pilih alat"
                                        class="min-w-[12rem] flex-1 rounded-sm border-marian-950/20 bg-white text-marian-950 focus:border-marian-800 focus:ring-marian-800">
                                    <option value="">— Pilih alat —</option>
                                    @foreach ($barang as $b)
                                        <option value="{{ $b->id }}">{{ $b->nama }}</option>
                                    @endforeach
                                </select>
                                {{-- Jumlah dengan tombol - / + --}}
                                <div class="flex items-center border border-marian-950/20 bg-white">
                                    <button type="button" @click="baris.jumlah = Math.max(1, (parseInt(baris.jumlah) || 1) - 1)" class="h-10 w-10 text-lg text-marian-800 hover:bg-marian-50" aria-label="Kurangi">−</button>
                                    <input type="number" min="1" :name="`items[${i}][jumlah]`" x-model.number="baris.jumlah" aria-label="Jumlah"
                                           class="w-12 border-0 border-x border-marian-950/20 bg-transparent p-0 text-center font-mono font-semibold focus:ring-0">
                                    <button type="button" @click="baris.jumlah = (parseInt(baris.jumlah) || 0) + 1" class="h-10 w-10 text-lg text-marian-800 hover:bg-marian-50" aria-label="Tambah">+</button>
                                </div>
                                <button type="button" @click="hapus(i)" x-show="items.length > 1"
                                        class="px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Hapus</button>
                            </div>
                            <div class="mt-2 px-1 text-xs" x-show="baris.barang_id && stok[baris.barang_id] !== undefined">
                                <span class="font-mono font-medium" :class="stok[baris.barang_id] >= baris.jumlah ? 'text-marian-800' : 'text-red-600'"
                                      x-text="'Sisa ' + stok[baris.barang_id] + ' unit pada tanggal tersebut'"></span>
                            </div>
                        </div>
                    </template>
                </div>

                @foreach ($errors->get('items*') as $pesan)
                    <x-input-error :messages="$pesan" class="mt-2" />
                @endforeach

                <button type="button" @click="tambah()" class="mt-3 inline-flex items-center gap-1 border border-dashed border-marian-800 px-3 py-1.5 text-sm font-medium text-marian-800 hover:bg-marian-50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14" /></svg>
                    Tambah alat
                </button>
            </fieldset>

            {{-- 4. Tujuan --}}
            <fieldset class="border-t border-marian-950/15 pt-6">
                <legend class="flex items-center gap-3 text-base font-semibold">
                    <span class="font-mono text-sm text-marian-800">04 /</span> Tujuan Peminjaman
                </legend>
                <textarea id="tujuan" name="tujuan" rows="3" minlength="5" maxlength="500" required aria-label="Tujuan peminjaman"
                          class="mt-4 block w-full rounded-sm border-marian-950/20 text-marian-950 placeholder:text-marian-950/35 focus:border-marian-800 focus:ring-marian-800"
                          placeholder="Mis. Inisiasi eksplan anggrek untuk praktikum Kultur Jaringan Tanaman">{{ old('tujuan') }}</textarea>
                <x-input-error :messages="$errors->get('tujuan')" class="mt-2" />
            </fieldset>

            <div class="flex flex-wrap items-center gap-3 border-t border-marian-950/15 pt-6">
                <x-primary-button class="px-7 py-3">
                    Kirim Ajuan
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </x-primary-button>
                <a href="{{ route('peminjam.katalog.index') }}" class="text-sm font-medium text-marian-950/60 hover:text-marian-900">Batal</a>
            </div>
        </form>

        {{-- Ringkasan (menempel saat digulir di layar lebar) --}}
        <aside class="space-y-4 lg:sticky lg:top-24">
            <div class="panel border-t-4 border-t-marian-800 p-6">
                <h2 class="label-bagian">Ringkasan</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-marian-950/60">Lama pinjam</dt><dd class="font-mono font-semibold" x-text="lama() !== null ? lama() + ' hari' : '—'"></dd></div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-marian-950/60">Total unit</dt>
                        <dd class="font-mono font-semibold" :class="total() > maksUnit && 'text-red-600'" x-text="total() + ' / ' + maksUnit"></dd>
                    </div>
                </dl>
                <ul class="mt-4 space-y-1.5 border-t border-marian-950/15 pt-4 text-sm">
                    <template x-for="b in items.filter(b => b.barang_id)" :key="b.barang_id">
                        <li class="flex justify-between gap-3"><span class="truncate" x-text="nama[b.barang_id]"></span><span class="font-mono font-semibold" x-text="'×' + b.jumlah"></span></li>
                    </template>
                    <li x-show="!items.some(b => b.barang_id)" class="text-marian-950/50">Belum ada alat dipilih.</li>
                </ul>
            </div>
            <div class="panel p-5 text-sm text-marian-950/75">
                <div class="label-bagian">Saat mengambil alat</div>
                <ul class="mt-3 space-y-1 text-xs">
                    <li>— Tunjukkan QR kode peminjaman (layar atau email).</li>
                    <li>— Bawa kartu identitas asli (KTM/KTP).</li>
                    <li>— Alat dipakai di lingkungan laboratorium.</li>
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
