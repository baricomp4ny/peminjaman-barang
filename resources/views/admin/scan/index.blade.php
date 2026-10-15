<x-app-layout>
    <x-slot name="header"><span class="text-sm text-gray-500">Scan QR Code</span></x-slot>

    <div x-data="halamanScan(@js([
            'mode' => $mode,
            'awal' => $awal,
            'urlCari' => route('admin.scan.cari'),
            'urlCek' => route('admin.scan.cek-unit'),
            'urlSerah' => route('admin.peminjaman.serahkan', '__ID__'),
            'urlKembali' => route('admin.pengembalian.store', '__ID__'),
            'hariIni' => now()->toDateString(),
        ]))" x-init="mulai()" @beforeunload.window="matikanKamera()">

        {{-- Pilihan mode --}}
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <h2 class="me-2 text-xl font-semibold text-gray-800">Scan QR</h2>
            @foreach (['serah' => 'Serah-terima', 'kembali' => 'Pengembalian'] as $nilai => $label)
                <a href="{{ route('admin.scan.index', ['mode' => $nilai]) }}"
                   class="rounded-full px-4 py-1.5 text-sm font-medium {{ $mode === $nilai ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 shadow-sm hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            {{-- Kiri: kamera + input manual --}}
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-lg bg-white p-4 shadow">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Kamera</h3>
                        <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                x-text="kameraAktif ? 'Matikan kamera' : 'Nyalakan kamera'" @click="kameraAktif ? matikanKamera() : nyalakanKamera()"></button>
                    </div>
                    <div id="pembaca-qr" class="overflow-hidden rounded-md bg-gray-900" x-show="kameraAktif"></div>
                    <p class="text-sm text-gray-500" x-show="!kameraAktif && !pesanKamera">Kamera mati. Nyalakan untuk scan dengan kamera, atau pakai input manual di bawah.</p>
                    <p class="rounded-md bg-amber-50 p-3 text-sm text-amber-800" x-show="pesanKamera" x-text="pesanKamera"></p>
                </div>

                <div class="rounded-lg bg-white p-4 shadow">
                    <form @submit.prevent="kirimManual()">
                        <x-input-label for="kode_manual" value="Input kode manual" />
                        <div class="mt-1 flex gap-2">
                            <x-text-input id="kode_manual" x-ref="manual" x-model="manual" class="block w-full font-mono uppercase"
                                          autocomplete="off" placeholder="PJM-… / PRJ-001" />
                            <x-primary-button x-bind:disabled="sibuk">Proses</x-primary-button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Ketik kode lalu tekan Enter. Bisa kode peminjaman, kartu peminjam, atau kode unit.</p>
                    </form>
                </div>

                {{-- Umpan balik scan terakhir --}}
                <div x-show="pesan.teks" x-transition
                     :class="{ 'border-green-300 bg-green-50 text-green-800': pesan.jenis === 'ok', 'border-red-300 bg-red-50 text-red-800': pesan.jenis === 'gagal', 'border-blue-200 bg-blue-50 text-blue-800': pesan.jenis === 'info' }"
                     class="rounded-lg border p-4 text-sm font-medium" role="status" aria-live="polite">
                    <span x-text="pesan.teks"></span>
                </div>
            </div>

            {{-- Kanan: transaksi --}}
            <div class="lg:col-span-3">
                {{-- Belum ada transaksi --}}
                <div x-show="!transaksi && pilihan.length === 0" class="rounded-lg bg-white p-6 shadow">
                    <h3 class="font-semibold text-gray-800">Langkah 1: buka transaksi</h3>
                    @if ($mode === 'serah')
                        <p class="mt-2 text-sm text-gray-600">Scan <strong>kartu QR peminjam</strong> atau ketik <strong>kode peminjaman</strong> (PJM-…). Ajuan yang muncul hanya yang berstatus <em>disetujui</em>.</p>
                    @else
                        <p class="mt-2 text-sm text-gray-600">Scan <strong>QR salah satu unit</strong> yang dikembalikan, <strong>kartu QR peminjam</strong>, atau ketik <strong>kode peminjaman</strong>.</p>
                    @endif
                </div>

                {{-- Pilih transaksi bila peminjam punya lebih dari satu --}}
                <div x-show="!transaksi && pilihan.length > 0" class="rounded-lg bg-white p-6 shadow">
                    <h3 class="font-semibold text-gray-800">Pilih transaksi</h3>
                    <div class="mt-3 space-y-2">
                        <template x-for="t in pilihan" :key="t.id">
                            <button type="button" @click="buka(t)" class="block w-full rounded-md border p-3 text-left text-sm hover:bg-gray-50">
                                <span class="font-mono" x-text="t.kode"></span> · <span x-text="t.peminjam"></span>
                                <span class="block text-gray-500" x-text="t.tanggal + ' — ' + t.detail.map(d => d.nama + ' ×' + d.jumlah).join(', ')"></span>
                            </button>
                        </template>
                    </div>
                    <button type="button" class="mt-3 text-sm text-gray-500 hover:text-gray-700" @click="reset()">Batal</button>
                </div>

                {{-- Transaksi aktif --}}
                <template x-if="transaksi">
                    <div class="rounded-lg bg-white p-6 shadow">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <a :href="transaksi.url_detail" class="font-mono text-sm text-indigo-600 hover:underline" x-text="transaksi.kode"></a>
                                <div class="text-lg font-semibold text-gray-800" x-text="transaksi.peminjam"></div>
                                <div class="text-sm text-gray-500"><span x-text="transaksi.nomor_identitas"></span> · <span x-text="transaksi.tanggal"></span></div>
                            </div>
                            <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="reset()">Ganti transaksi</button>
                        </div>

                        <p x-show="transaksi.peringatan" class="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800" x-text="transaksi.peringatan"></p>
                        <p x-show="transaksi.terlambat > 0" class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-800" x-text="'Terlambat ' + transaksi.terlambat + ' hari — denda dihitung otomatis saat disimpan.'"></p>

                        <h3 class="mt-5 text-sm font-semibold text-gray-800">
                            Langkah 2: scan QR setiap unit
                            <span class="font-normal text-gray-500" x-text="mode === 'serah' ? '(harus sesuai jenis & jumlah ajuan)' : '(boleh sebagian)'"></span>
                        </h3>

                        {{-- Progres per jenis barang (serah-terima) --}}
                        <ul class="mt-2 space-y-1 text-sm" x-show="mode === 'serah'">
                            <template x-for="d in transaksi.detail" :key="d.barang_id">
                                <li class="flex items-center justify-between rounded-md border px-3 py-2"
                                    :class="jumlahTerscan(d.barang_id) === d.jumlah ? 'border-green-300 bg-green-50' : ''">
                                    <span x-text="d.nama"></span>
                                    <span class="font-semibold" x-text="jumlahTerscan(d.barang_id) + ' / ' + d.jumlah + (jumlahTerscan(d.barang_id) === d.jumlah ? ' ✔' : '')"></span>
                                </li>
                            </template>
                        </ul>

                        {{-- Unit yang masih dipinjam (pengembalian) --}}
                        <p class="mt-2 text-sm text-gray-600" x-show="mode === 'kembali'">
                            Belum kembali:
                            <template x-for="u in transaksi.belum_kembali" :key="u.unit_id">
                                <span class="me-1 inline-block rounded px-1.5 font-mono text-xs"
                                      :class="sudahDipindai(u.unit_id) ? 'bg-green-100 text-green-800 line-through' : 'bg-gray-100'" x-text="u.kode_unit"></span>
                            </template>
                        </p>

                        {{-- Daftar hasil scan --}}
                        <div class="mt-4 divide-y rounded-md border" x-show="dipindai.length > 0">
                            <template x-for="(u, i) in dipindai" :key="u.unit_id">
                                <div class="grid items-center gap-2 p-3 sm:grid-cols-12">
                                    <div class="sm:col-span-4">
                                        <span class="font-mono text-sm font-semibold" x-text="u.kode_unit"></span>
                                        <span class="block text-xs text-gray-500" x-text="u.nama"></span>
                                    </div>
                                    <template x-if="mode === 'kembali'">
                                        <select x-model="u.kondisi" class="rounded-md border-gray-300 text-sm shadow-sm sm:col-span-3" :aria-label="'Kondisi ' + u.kode_unit">
                                            <option value="baik">Baik</option>
                                            <option value="rusak">Rusak</option>
                                            <option value="hilang">Hilang</option>
                                        </select>
                                    </template>
                                    <template x-if="mode === 'kembali'">
                                        <input x-model="u.catatan" maxlength="255" placeholder="Catatan (opsional)"
                                               class="rounded-md border-gray-300 text-sm shadow-sm sm:col-span-4">
                                    </template>
                                    <button type="button" @click="hapus(i)" class="text-sm text-red-600 hover:text-red-800 sm:col-span-1 sm:text-right" :class="mode === 'serah' && 'sm:col-start-12'">Hapus</button>
                                </div>
                            </template>
                        </div>

                        {{-- Langkah 3: simpan (form biasa ke endpoint yang sama dengan form Sprint 3) --}}
                        <form method="POST" :action="urlSimpan()" class="mt-5 border-t pt-4" @submit="matikanKamera()">
                            @csrf
                            <input type="hidden" name="dari_scan" value="1">
                            <template x-if="mode === 'serah'">
                                <div>
                                    <template x-for="u in dipindai" :key="u.unit_id">
                                        <input type="hidden" name="kode_unit[]" :value="u.kode_unit">
                                    </template>
                                </div>
                            </template>
                            <template x-if="mode === 'kembali'">
                                <div>
                                    <input type="hidden" name="tanggal_kembali" :value="cfg.hariIni">
                                    <template x-for="u in dipindai" :key="u.unit_id">
                                        <div>
                                            <input type="hidden" :name="'unit[' + u.unit_id + '][pilih]'" value="1">
                                            <input type="hidden" :name="'unit[' + u.unit_id + '][kondisi]'" :value="u.kondisi">
                                            <input type="hidden" :name="'unit[' + u.unit_id + '][catatan]'" :value="u.catatan">
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <x-primary-button x-bind:disabled="!bisaSimpan()" class="disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-text="mode === 'serah' ? 'Selesaikan serah-terima' : 'Simpan pengembalian (' + dipindai.length + ' unit)'"></span>
                            </x-primary-button>
                            <p class="mt-1 text-xs text-gray-500" x-show="mode === 'serah' && !lengkap()">Tombol aktif setelah semua jenis barang lengkap.</p>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Library scan kamera disimpan lokal (bukan CDN) --}}
    <script src="{{ asset('vendor/html5-qrcode/html5-qrcode.min.js') }}"></script>
    <script>
        function halamanScan(cfg) {
            return {
                cfg,
                mode: cfg.mode,
                transaksi: null,
                pilihan: [],
                dipindai: [],
                manual: '',
                pesan: { jenis: '', teks: '' },
                sibuk: false,
                terakhir: { kode: '', waktu: 0 },
                kamera: null,
                kameraAktif: false,
                pesanKamera: '',

                mulai() {
                    if (cfg.awal) this.proses(cfg.awal);
                    this.$nextTick(() => this.$refs.manual?.focus());
                },

                // ---------- Masukan kode (kamera & manual memakai fungsi yang sama) ----------
                kirimManual() {
                    const kode = this.manual.trim();
                    this.manual = '';
                    if (!kode) { this.tampil('gagal', 'Kode kosong. Scan QR atau ketik kodenya.'); return; }
                    this.proses(kode, true);
                },

                async proses(kode, dariManual = false) {
                    kode = kode.trim();
                    const now = Date.now();
                    // Cegah scan ganda: kamera bisa membaca QR yang sama berkali-kali per detik
                    if (!dariManual && kode === this.terakhir.kode && now - this.terakhir.waktu < 2500) return;
                    if (this.sibuk) return;
                    this.terakhir = { kode, waktu: now };
                    this.sibuk = true;
                    try {
                        if (!this.transaksi) await this.cari(kode);
                        else await this.cekUnit(kode);
                    } finally {
                        this.sibuk = false;
                        this.$refs.manual?.focus();
                    }
                },

                async kirim(url, data) {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify(data),
                    });
                    let json = {};
                    try { json = await res.json(); } catch (e) { /* respons bukan JSON */ }
                    if (!res.ok) {
                        const pesan = json.pesan || (json.errors ? Object.values(json.errors)[0][0] : null) || 'Terjadi kesalahan (' + res.status + ').';
                        throw new Error(pesan);
                    }
                    return json;
                },

                async cari(kode) {
                    try {
                        const json = await this.kirim(cfg.urlCari, { kode, mode: this.mode });
                        if (json.transaksi.length === 1) this.buka(json.transaksi[0], kode);
                        else { this.pilihan = json.transaksi; this.tampil('info', 'Peminjam memiliki ' + json.transaksi.length + ' transaksi. Pilih salah satu.'); this.bunyi(true); }
                    } catch (e) { this.tampil('gagal', e.message); this.bunyi(false); }
                },

                buka(t, kodeAwal = null) {
                    this.transaksi = t;
                    this.pilihan = [];
                    this.dipindai = [];
                    this.tampil('ok', 'Transaksi ' + t.kode + ' (' + t.peminjam + ') dibuka. Scan unitnya.');
                    this.bunyi(true);
                    // Mode pengembalian dibuka dari scan unit: unit itu langsung dimasukkan
                    if (this.mode === 'kembali' && kodeAwal && t.belum_kembali.some(u => u.kode_unit === kodeAwal.toUpperCase())) {
                        this.cekUnit(kodeAwal);
                    }
                },

                async cekUnit(kode) {
                    try {
                        const json = await this.kirim(cfg.urlCek, {
                            peminjaman_id: this.transaksi.id, kode, mode: this.mode, sudah: this.dipindai.map(u => u.unit_id),
                        });
                        this.dipindai.push({ ...json.unit, kondisi: 'baik', catatan: '' });
                        let teks = '✔ ' + json.unit.kode_unit + ' (' + json.unit.nama + ') diterima.';
                        if (this.mode === 'serah' && this.lengkap()) teks += ' Semua lengkap — klik "Selesaikan serah-terima".';
                        this.tampil('ok', teks);
                        this.bunyi(true);
                    } catch (e) { this.tampil('gagal', '✖ ' + e.message); this.bunyi(false); }
                },

                // ---------- Status daftar ----------
                jumlahTerscan(barangId) { return this.dipindai.filter(u => u.barang_id === barangId).length; },
                sudahDipindai(unitId) { return this.dipindai.some(u => u.unit_id === unitId); },
                lengkap() { return !!this.transaksi && this.transaksi.detail.every(d => this.jumlahTerscan(d.barang_id) === d.jumlah); },
                bisaSimpan() {
                    if (!this.transaksi || this.sibuk) return false;
                    return this.mode === 'serah' ? this.lengkap() && !this.transaksi.peringatan : this.dipindai.length > 0;
                },
                urlSimpan() {
                    if (!this.transaksi) return '#';
                    return (this.mode === 'serah' ? cfg.urlSerah : cfg.urlKembali).replace('__ID__', this.transaksi.id);
                },
                hapus(i) { const u = this.dipindai.splice(i, 1)[0]; this.tampil('info', u.kode_unit + ' dihapus dari daftar.'); },
                reset() { this.transaksi = null; this.pilihan = []; this.dipindai = []; this.tampil('', ''); this.$refs.manual?.focus(); },
                tampil(jenis, teks) { this.pesan = { jenis, teks }; },

                // ---------- Kamera ----------
                async nyalakanKamera() {
                    this.pesanKamera = '';
                    // Browser hanya mengizinkan kamera di HTTPS atau localhost
                    if (!window.isSecureContext || !navigator.mediaDevices) {
                        this.pesanKamera = 'Kamera tidak tersedia karena halaman tidak dibuka lewat HTTPS/localhost. Aktifkan SSL di Laragon lalu buka https://…, atau gunakan input manual.';
                        return;
                    }
                    if (typeof Html5Qrcode === 'undefined') { this.pesanKamera = 'Library scan QR gagal dimuat.'; return; }
                    this.kameraAktif = true;
                    await this.$nextTick();
                    try {
                        this.kamera = this.kamera || new Html5Qrcode('pembaca-qr');
                        await this.kamera.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } },
                            (teks) => this.proses(teks), () => {});
                    } catch (e) {
                        this.kameraAktif = false;
                        this.pesanKamera = 'Kamera tidak bisa dibuka: ' + (e?.message || e) + '. Pastikan izin kamera diberikan, atau gunakan input manual.';
                    }
                },
                async matikanKamera() {
                    if (this.kamera && this.kameraAktif) { try { await this.kamera.stop(); } catch (e) {} }
                    this.kameraAktif = false;
                },

                // Bunyi singkat: nada tinggi = berhasil, nada rendah = gagal
                bunyi(berhasil) {
                    try {
                        const ctx = this._audio || (this._audio = new (window.AudioContext || window.webkitAudioContext)());
                        const osc = ctx.createOscillator(); const gain = ctx.createGain();
                        osc.frequency.value = berhasil ? 880 : 220; gain.gain.value = 0.1;
                        osc.connect(gain); gain.connect(ctx.destination);
                        osc.start(); osc.stop(ctx.currentTime + (berhasil ? 0.12 : 0.35));
                    } catch (e) { /* audio tidak didukung */ }
                    // Getar hanya diizinkan browser setelah pengguna pernah menyentuh/klik halaman
                    if (navigator.vibrate && navigator.userActivation?.hasBeenActive) navigator.vibrate(berhasil ? 60 : [80, 60, 80]);
                },
            };
        }
    </script>
</x-app-layout>
