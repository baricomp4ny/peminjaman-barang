/**
 * Komponen Alpine halaman Scan QR (admin).
 *
 * Sumber kode yang didukung:
 *  1. Kamera (html5-qrcode, file lokal di public/vendor)
 *  2. Scanner USB 2D — bekerja seperti keyboard: mengetik sangat cepat lalu Enter/Tab
 *  3. Ketik manual + Enter
 * Semua masuk ke antrean yang diproses berurutan, sehingga tidak ada scan yang hilang.
 */

const JEDA_SCANNER_MS = 50;    // jeda antar-karakter scanner USB biasanya < 30 ms; manusia > 80 ms
const MIN_PANJANG_SCAN = 4;
const MAKS_PANJANG_KODE = 100;
const JEDA_SCAN_GANDA_MS = 2500;
const BATAS_WAKTU_MS = 8000;

/** Bersihkan kode hasil scan: karakter kontrol, spasi, prefix simbologi AIM (mis. "]Q1"). */
export function bersihkanKode(kode) {
    return String(kode ?? '')
        .replace(/[\u0000-\u001F\u007F]/g, '')
        .trim()
        .replace(/^\][A-Za-z]\d/, '')
        .trim();
}

function bisaDiketik(el) {
    return el && (el.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName));
}

function interaktif(el) {
    return el && el.closest('a, button, input, select, textarea, label, [contenteditable], #pembaca-qr');
}

export default function halamanScan(cfg) {
    return {
        cfg,
        mode: cfg.mode,
        transaksi: null,
        pilihan: [],
        dipindai: [],
        manual: '',
        pesan: { jenis: '', teks: '', n: 0 },
        sibuk: false,
        antrean: [],
        terakhir: { kode: '', waktu: 0 },
        scannerSiap: false,
        kamera: null,
        kameraAktif: false,
        pesanKamera: '',
        // penampung ketikan cepat scanner USB saat fokus TIDAK di input manual
        _buf: { teks: '', waktu: 0, target: null, nilaiAwal: null, cepat: true },

        init() {
            this.pasangPenangkapScanner();
            if (cfg.awal) this.proses(cfg.awal, 'sistem');
            this.$nextTick(() => this.fokus());
        },

        // ---------------------------------------------------------------
        // Fokus & indikator "Scanner siap"
        // ---------------------------------------------------------------
        fokus() {
            const el = this.$refs.manual;
            if (!el) return;
            // Jangan rebut fokus dari kolom yang sedang diisi pengguna (kondisi/catatan)
            if (bisaDiketik(document.activeElement) && document.activeElement !== el) return;
            el.focus({ preventScroll: true });
            this.cekSiap();
        },
        paksaFokus() {
            this.$refs.manual?.focus({ preventScroll: true });
            this.cekSiap();
        },
        cekSiap() {
            this.scannerSiap = document.hasFocus() && document.activeElement === this.$refs.manual;
        },

        pasangPenangkapScanner() {
            window.addEventListener('focus', () => setTimeout(() => this.fokus(), 0));
            window.addEventListener('blur', () => this.cekSiap());
            document.addEventListener('focusin', () => this.cekSiap());
            document.addEventListener('focusout', () => setTimeout(() => this.cekSiap(), 0));
            // Klik di area kosong → kembalikan fokus ke input scanner
            document.addEventListener('click', (e) => { if (!interaktif(e.target)) this.fokus(); });
            // Penangkap global: dipasang di fase capture supaya jalan sebelum handler lain
            window.addEventListener('keydown', (e) => this.tanganiTombol(e), true);
        },

        /**
         * Mengenali scanner USB yang mengetik saat fokus TIDAK di input manual.
         *  - Fokus di area kosong → semua ketikan + Enter dianggap kode.
         *  - Fokus di kolom lain (catatan) → hanya ketikan super cepat yang dianggap scan,
         *    dan isi kolom dikembalikan seperti semula.
         */
        tanganiTombol(e) {
            const manual = this.$refs.manual;
            const target = e.target;

            // Di input manual: Tab dari scanner (suffix Tab) diperlakukan seperti Enter
            if (target === manual) {
                if (e.key === 'Tab' && this.manual.trim() !== '') { e.preventDefault(); this.kirimManual(); }
                return;
            }
            if (e.ctrlKey || e.altKey || e.metaKey) return;

            const sekarang = performance.now();
            const b = this._buf;

            if (e.key.length === 1) {
                const jeda = sekarang - b.waktu;
                if (jeda > 300 || b.target !== target) {
                    // awal rangkaian baru: simpan isi kolom sebelum scanner mengetik
                    b.teks = ''; b.cepat = true; b.target = target;
                    b.nilaiAwal = bisaDiketik(target) && 'value' in target ? target.value : null;
                } else if (jeda > JEDA_SCANNER_MS) {
                    b.cepat = false;
                }
                b.teks += e.key;
                b.waktu = sekarang;
                if (!bisaDiketik(target)) e.preventDefault(); // cegah scroll halaman oleh spasi dll.
                return;
            }

            if (e.key === 'Enter' || e.key === 'Tab') {
                const kode = b.teks;
                const dariScanner = kode.length >= MIN_PANJANG_SCAN && (sekarang - b.waktu) < 300
                    && (!bisaDiketik(target) || b.cepat);
                b.teks = '';
                if (!dariScanner) return;

                e.preventDefault();
                e.stopPropagation();
                // Kembalikan isi kolom yang "terkotori" oleh ketikan scanner
                if (b.nilaiAwal !== null && 'value' in target) {
                    target.value = b.nilaiAwal;
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                }
                this.proses(kode, 'scanner');
            }
        },

        // ---------------------------------------------------------------
        // Antrean pemrosesan
        // ---------------------------------------------------------------
        kirimManual() {
            const kode = this.manual;
            this.manual = '';
            this.proses(kode, 'manual');
        },

        /** sumber: 'kamera' | 'scanner' | 'manual' | 'sistem' */
        proses(kodeMentah, sumber = 'manual') {
            const kode = bersihkanKode(kodeMentah);

            if (!kode) { this.tampil('gagal', 'Kode kosong. Scan QR atau ketik kodenya.'); this.bunyi(false); return; }
            if (kode.length > MAKS_PANJANG_KODE) {
                this.tampil('gagal', `Kode terlalu panjang (${kode.length} karakter). Pastikan yang di-scan adalah QR dari aplikasi ini.`);
                this.bunyi(false);
                return;
            }

            // Cegah scan ganda: kode yang sama dalam 2,5 detik diabaikan (kamera/scanner sering membaca 2x)
            const sekarang = Date.now();
            const sama = kode.toUpperCase() === this.terakhir.kode && sekarang - this.terakhir.waktu < JEDA_SCAN_GANDA_MS;
            if (sama && sumber !== 'manual') {
                if (sumber === 'scanner') this.tampil('info', `${kode} diabaikan (scan ganda).`);
                return;
            }
            this.terakhir = { kode: kode.toUpperCase(), waktu: sekarang };

            this.antrean.push(kode);
            this.jalankanAntrean();
        },

        async jalankanAntrean() {
            if (this.sibuk) return;
            this.sibuk = true;
            try {
                while (this.antrean.length) {
                    const kode = this.antrean.shift();
                    if (!this.transaksi) await this.cari(kode);
                    else await this.cekUnit(kode);
                }
            } finally {
                this.sibuk = false;
                this.fokus();
            }
        },

        // ---------------------------------------------------------------
        // Komunikasi server (dengan pesan error yang jelas)
        // ---------------------------------------------------------------
        async kirim(url, data) {
            const kontrol = new AbortController();
            const timer = setTimeout(() => kontrol.abort(), BATAS_WAKTU_MS);
            let res;
            try {
                res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(data),
                    signal: kontrol.signal,
                });
            } catch (e) {
                throw new Error(e.name === 'AbortError'
                    ? 'Server tidak merespons (lebih dari 8 detik). Coba scan lagi.'
                    : 'Koneksi ke server terputus. Periksa jaringan lalu coba lagi.');
            } finally {
                clearTimeout(timer);
            }

            let json = {};
            try { json = await res.json(); } catch (e) { /* respons bukan JSON */ }

            if (res.ok) return json;
            if (res.status === 419) throw new Error('Sesi sudah habis. Muat ulang halaman (F5) lalu scan lagi.');
            if (res.status === 401) throw new Error('Anda sudah logout. Silakan login kembali.');
            if (res.status === 403) throw new Error('Akses ditolak. Halaman scan hanya untuk admin.');
            if (res.status === 422) throw new Error(json.pesan || (json.errors ? Object.values(json.errors)[0][0] : 'Data tidak valid.'));
            throw new Error(`Terjadi kesalahan di server (${res.status}). Coba lagi atau hubungi pengelola.`);
        },

        async cari(kode) {
            try {
                const json = await this.kirim(cfg.urlCari, { kode, mode: this.mode });
                if (json.transaksi.length === 1) {
                    await this.buka(json.transaksi[0], kode);
                } else {
                    this.pilihan = json.transaksi;
                    this.tampil('info', `Peminjam memiliki ${json.transaksi.length} transaksi. Pilih salah satu.`);
                    this.bunyi(true);
                }
            } catch (e) {
                this.tampil('gagal', e.message);
                this.bunyi(false);
            }
        },

        async buka(t, kodeAwal = null) {
            this.transaksi = t;
            this.pilihan = [];
            this.dipindai = [];
            this.tampil('ok', `Transaksi ${t.kode} (${t.peminjam}) dibuka. Scan unitnya.`);
            this.bunyi(true);
            // Mode pengembalian dibuka dari scan unit: unit itu langsung dimasukkan
            if (this.mode === 'kembali' && kodeAwal && t.belum_kembali.some(u => u.kode_unit === kodeAwal.toUpperCase())) {
                await this.cekUnit(kodeAwal);
            }
            this.fokus();
        },

        async cekUnit(kode) {
            try {
                const json = await this.kirim(cfg.urlCek, {
                    peminjaman_id: this.transaksi.id, kode, mode: this.mode, sudah: this.dipindai.map(u => u.unit_id),
                });
                this.dipindai.push({ ...json.unit, kondisi: 'baik', catatan: '' });
                let teks = `✔ ${json.unit.kode_unit} (${json.unit.nama}) diterima.`;
                if (this.mode === 'serah' && this.lengkap()) teks += ' Semua lengkap — klik "Selesaikan serah-terima".';
                this.tampil('ok', teks);
                this.bunyi(true);
            } catch (e) {
                this.tampil('gagal', '✖ ' + e.message);
                this.bunyi(false);
            }
        },

        // ---------------------------------------------------------------
        // Status daftar
        // ---------------------------------------------------------------
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
        hapus(i) {
            const u = this.dipindai.splice(i, 1)[0];
            this.terakhir = { kode: '', waktu: 0 }; // unit yang dihapus boleh langsung di-scan ulang
            this.tampil('info', `${u.kode_unit} dihapus dari daftar.`);
            this.fokus();
        },
        reset() {
            this.transaksi = null; this.pilihan = []; this.dipindai = []; this.antrean = [];
            this.terakhir = { kode: '', waktu: 0 };
            this.tampil('', '');
            this.paksaFokus();
        },
        // n bertambah setiap pesan baru → dipakai untuk memicu animasi kedip
        tampil(jenis, teks) { this.pesan = { jenis, teks, n: this.pesan.n + 1 }; },

        // ---------------------------------------------------------------
        // Kamera
        // ---------------------------------------------------------------
        async nyalakanKamera() {
            this.pesanKamera = '';
            // Browser hanya mengizinkan kamera di HTTPS atau localhost
            if (!window.isSecureContext || !navigator.mediaDevices) {
                this.pesanKamera = 'Kamera tidak tersedia karena halaman tidak dibuka lewat HTTPS/localhost. Aktifkan SSL di Laragon lalu buka https://…, atau gunakan scanner USB / input manual.';
                return;
            }
            if (typeof window.Html5Qrcode === 'undefined') { this.pesanKamera = 'Library scan QR gagal dimuat.'; return; }
            this.kameraAktif = true;
            await this.$nextTick();
            try {
                this.kamera = this.kamera || new window.Html5Qrcode('pembaca-qr');
                await this.kamera.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } },
                    (teks) => this.proses(teks, 'kamera'), () => {});
            } catch (e) {
                this.kameraAktif = false;
                this.pesanKamera = `Kamera tidak bisa dibuka: ${e?.message || e}. Pastikan izin kamera diberikan, atau gunakan scanner USB / input manual.`;
            }
            this.fokus();
        },
        async matikanKamera() {
            if (this.kamera && this.kameraAktif) { try { await this.kamera.stop(); } catch (e) { /* sudah berhenti */ } }
            this.kameraAktif = false;
        },

        // Bunyi singkat: nada tinggi = berhasil, nada rendah = gagal
        bunyi(berhasil) {
            try {
                const ctx = this._audio || (this._audio = new (window.AudioContext || window.webkitAudioContext)());
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.frequency.value = berhasil ? 880 : 220;
                gain.gain.value = 0.1;
                osc.connect(gain); gain.connect(ctx.destination);
                osc.start(); osc.stop(ctx.currentTime + (berhasil ? 0.12 : 0.35));
            } catch (e) { /* audio tidak didukung */ }
            // Getar hanya diizinkan browser setelah pengguna pernah menyentuh/klik halaman
            if (navigator.vibrate && navigator.userActivation?.hasBeenActive) navigator.vibrate(berhasil ? 60 : [80, 60, 80]);
        },
    };
}
