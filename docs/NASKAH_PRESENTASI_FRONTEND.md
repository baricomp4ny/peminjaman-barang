# Naskah Presentasi Front End
**Sistem Informasi Peminjaman Barang Berbasis Web dengan QR Code**
Laboratorium Kultur Jaringan, Sekolah Vokasi IPB University

Durasi ± 6–7 menit. Teks *miring dalam kurung* = aksi saat demo.

---

## 1. Pembuka (± 30 detik)

> Selamat pagi/siang. Saya akan menjelaskan bagian **front end**, yaitu tampilan yang dilihat dan dipakai langsung oleh peminjam dan admin.
>
> Front end sistem ini dibangun dengan tiga teknologi utama:
> - **Blade**, template HTML dari Laravel, supaya halaman bisa menampilkan data langsung dari database, seperti daftar alat dan sisa unit.
> - **Tailwind CSS** untuk mengatur tampilan dan membuatnya responsif di HP maupun laptop.
> - **Alpine.js** untuk interaksi ringan seperti membuka menu dan filter.
>
> Untuk fitur QR Code, kami memakai library **html5-qrcode** agar bisa scan lewat kamera, dan sistem juga mendukung **scanner USB**. Semua file CSS dan JavaScript digabung serta diperkecil oleh **Vite** supaya halaman cepat dimuat.

---

## 2. Konsep Desain (± 45 detik)

*(Buka halaman beranda.)*

> Desainnya mengikuti identitas laboratorium dengan pola warna **80/15/5**:
> - **80% biru Marian** sebagai warna utama,
> - **15% emas** sebagai penanda, misalnya ikon fitur,
> - **5% magenta** sebagai aksen, misalnya garis tepi kartu alat saat dipilih.
>
> Warna **merah hanya dipakai untuk pesan gagal**, seperti ajuan ditolak atau scan salah, sehingga pengguna langsung sadar kalau ada masalah.
>
> Gaya tampilannya memakai kartu bersudut membulat dengan bayangan lembut. Jumlah kotak sengaja kami kurangi supaya halaman terlihat bersih dan tidak ramai.

---

## 3. Sisi Peminjam, Tanpa Login (± 2 menit)

> Peminjam, yaitu mahasiswa atau dosen, **tidak perlu membuat akun**. Mereka cukup membuka web dan langsung bisa melihat katalog.

**a. Katalog** *(gulir ke bagian katalog)*
> Di bagian katalog ada **filter di sebelah kiri**: pencarian nama atau kode alat, **cek tanggal**, kategori, lokasi, dan alat yang paling sering dipinjam.
> Fitur cek tanggal penting karena sisa unit dihitung **sesuai tanggal yang dipilih**, bukan hanya stok hari ini.
> *(Kecilkan layar / buka di HP.)* Di HP, filter pindah ke laci yang dibuka dengan tombol **Filter**, dan katalog tampil dua kolom.

**b. Detail alat** *(klik salah satu alat)*
> Di halaman detail terlihat foto, deskripsi, sisa unit, dan lama pinjam maksimal. Ada dua tombol: **Ajukan Peminjaman** dan **Lihat Alat Ini dalam Matriks**.

**c. Jadwal Alat** *(klik "Lihat Alat Ini dalam Matriks")*
> Ini matriks jadwal 14 hari ke depan. Warnanya punya arti:
> - **putih**: tersedia,
> - **biru muda**: sudah dipesan,
> - **biru tua**: sedang dipinjam,
> - **merah**: terlambat dikembalikan.
>
> Peminjam bisa melihat tanggal yang kosong, lalu klik sel kosong untuk langsung mengajukan di tanggal itu.

**d. Form ajuan** *(buka Ajukan Peminjaman)*
> Peminjam mengisi identitas, memilih alat dan jumlahnya, tanggal pinjam dan kembali, menyetujui syarat, lalu mengunggah surat peminjaman. Template surat bisa diunduh di bagian atas form.
> Setelah dikirim, muncul **kode peminjaman PJM beserta QR Code-nya**, yang juga dikirim ke email peminjam.

**e. Cek Status**
> Dengan memasukkan kode PJM dan NIM, peminjam bisa melihat status ajuan atau membatalkannya selama alat belum diambil.

---

## 4. Sisi Admin dan Fitur Scan QR (± 2 menit)

*(Login sebagai admin.)*

> Halaman admin memakai **sidebar** di sebelah kiri. Menu yang sedang dibuka ditandai, dan menu Ajuan Peminjaman punya **badge angka** untuk ajuan yang belum diverifikasi.

*(Buka menu Scan Barcode.)*

> Fitur utama front end ada di **halaman Scan**. Alurnya:
> 1. Admin scan **QR kode peminjaman** dari layar HP atau email peminjam. Daftar alat yang diajukan langsung muncul.
> 2. Admin scan **QR setiap unit alat**. Sistem langsung memeriksa apakah jenis alatnya sesuai ajuan, unitnya tersedia, dan jumlahnya tidak berlebih.
> 3. Setiap scan memberi **pesan hijau atau merah** disertai **bunyi beep**. Kalau salah, kotak pesan **berkedip merah** supaya petugas langsung melihatnya.
> 4. Tombol **Simpan** baru aktif setelah semua unit lengkap, lalu status berubah menjadi **Dipinjam**.
>
> Pengembalian memakai halaman yang sama: scan unit, pilih kondisinya (baik, rusak, atau hilang), lalu simpan.

> Ada beberapa hal teknis yang kami tangani di halaman ini:
> - **Scanner USB dikenali dari kecepatannya.** Scanner "mengetik" sangat cepat, kurang dari 50 milidetik per huruf, lalu menekan Enter. Jadi hasil scan tetap terbaca walaupun kursor tidak berada di kolom input.
> - **Mencegah scan ganda.** Kode yang sama diabaikan selama 2,5 detik, karena kamera bisa membaca QR yang sama berkali-kali.
> - **Antrean.** Kalau petugas scan dengan cepat berturut-turut, kode diproses satu per satu supaya tidak bentrok.
> - **Tanpa memuat ulang halaman.** Setiap kode dicek ke server di belakang layar, jadi prosesnya cepat.
> - Kalau kamera tidak bisa dipakai, kode juga bisa **diketik manual**.

---

## 5. Kualitas Front End (± 45 detik)

> Ada empat hal yang kami jaga di front end:
> 1. **Responsif**: tampilan rapi dari HP layar kecil 360 piksel sampai desktop, tanpa perlu menggeser ke samping.
> 2. **Komponen yang dipakai ulang**: kartu alat, matriks jadwal, dan ikon dibuat sekali lalu dipakai di banyak halaman. Desain jadi konsisten dan mudah diubah.
> 3. **Keamanan**: teks dari pengguna otomatis diamankan agar tidak bisa disisipi script, setiap form membawa token keamanan (CSRF), dan **semua pengecekan diulang di server**. Front end hanya untuk kenyamanan, bukan satu-satunya pengaman.
> 4. **Pesan yang jelas**: setiap aksi memberi pemberitahuan berhasil atau gagal.

---

## 6. Penutup (± 15 detik)

> Jadi, front end sistem ini dirancang supaya peminjam bisa mengajukan dengan mudah tanpa login, dan admin bisa mencatat serah-terima maupun pengembalian dengan cepat dan akurat lewat scan QR Code.
> Sekian penjelasan dari saya, terima kasih. Saya siap menjawab pertanyaan.

---

## Lampiran Kode: Apa dan Di Mana

Urutan lampiran mengikuti bagian naskah. Kalau penguji bertanya "kodenya mana?", buka file yang disebut.

### Peta folder front end

```
resources/
├── views/                         ← "HTML" (Blade)
│   ├── layouts/
│   │   ├── peminjam.blade.php     ← kerangka halaman publik (navbar + footer)
│   │   ├── admin.blade.php        ← kerangka halaman admin (sidebar)
│   │   └── partials/head.blade.php← <head>: font, @vite CSS & JS
│   ├── components/                ← potongan yang dipakai ulang
│   │   ├── kartu-alat.blade.php   ← kartu alat di katalog
│   │   ├── jadwal.blade.php       ← matriks jadwal (admin & publik)
│   │   └── ikon-alat.blade.php    ← ikon garis per jenis alat
│   ├── peminjam/                  ← halaman peminjam
│   │   ├── katalog/index.blade.php← beranda + katalog + filter
│   │   ├── katalog/show.blade.php ← detail alat
│   │   ├── jadwal/index.blade.php ← Jadwal Alat
│   │   ├── ajuan/create.blade.php ← form ajuan
│   │   └── status/                ← cek status & pembatalan
│   └── admin/                     ← halaman admin (scan/index.blade.php = halaman Scan)
├── css/app.css                    ← "style.css"
└── js/                            ← "script.js"
    ├── app.js                     ← pintu masuk JS (Alpine)
    ├── scan-qr.js                 ← logika halaman Scan
    ├── potong-foto.js             ← potong foto alat (Cropper.js)
    └── bootstrap.js               ← axios + token CSRF
tailwind.config.js                 ← warna, font, sudut, bayangan
vite.config.js                     ← penggabung file CSS & JS
public/vendor/html5-qrcode/        ← library kamera QR (lokal, bukan CDN)
```

---

### Bagian 1: teknologi, file `resources/js/app.js`

Alpine dijalankan di sini, dan komponen Scan + Potong Foto didaftarkan.

```js
import './bootstrap';
import Alpine from 'alpinejs';
import halamanScan from './scan-qr';
import potongFoto from './potong-foto';

window.Alpine = Alpine;

// Komponen halaman Scan QR harus terdaftar sebelum Alpine.start()
Alpine.data('halamanScan', halamanScan);
Alpine.data('potongFoto', potongFoto);

Alpine.start();
```
**Cara menjelaskan:** "Ini pintu masuk JavaScript. Komponen didaftarkan dulu, baru Alpine dijalankan."

CSS dan JS dimuat ke setiap halaman lewat `@vite([...])` di `resources/views/layouts/partials/head.blade.php`.

---

### Bagian 2: konsep desain, file `tailwind.config.js`

Semua warna diatur terpusat di sini.

```js
const marian = {
    50: '#EEF1FB', 100: '#DDE3F6', /* … */ 800: '#263C92', 900: '#1E2F72', 950: '#131E4A',
};

theme: {
    extend: {
        colors: {
            marian,
            indigo: marian,        // komponen bawaan ikut biru lab
            magenta: '#E01184',    // aksen 5%
            gold: '#FED80B',       // aksen 15%
        },
        fontFamily: { sans: ['"Plus Jakarta Sans"', /* … */] },
        borderRadius: { '4xl': '2rem', '5xl': '2.5rem' },   // sudut membulat
        boxShadow: { lembut: '0 10px 30px -12px rgba(38, 60, 146, …)' },
    },
},
```
**Cara menjelaskan:** "Karena warna ada di satu tempat, kalau warna lab berubah cukup ubah file ini. Di HTML cukup menulis class seperti `bg-marian-800` atau `ring-magenta`."

#### File `resources/css/app.css`
```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Kotak pesan scan gagal berkedip merah sebentar supaya terlihat petugas */
@keyframes kedip-gagal {
    0%, 100% { background-color: rgb(254 242 242); }
    25%, 75% { background-color: rgb(254 202 202); }
}
.kedip-gagal { animation: kedip-gagal 0.6s ease-in-out 2; }
```
**Cara menjelaskan:** "Hampir semua gaya ditulis sebagai class Tailwind. CSS buatan sendiri hanya animasi kedip merah untuk scan gagal."

---

### Bagian 3a: navbar, file `resources/views/layouts/peminjam.blade.php`

Daftar menu dibuat sebagai array, lalu diulang. Menu aktif ditandai lewat pola nama route.

```blade
@php
    $menu = [
        ['label' => 'Katalog Alat', 'route' => 'peminjam.katalog.index', 'aktif' => 'peminjam.katalog.*'],
        ['label' => 'Jadwal Alat', 'route' => 'peminjam.jadwal', 'aktif' => 'peminjam.jadwal'],
        ['label' => 'Ajukan Peminjaman', 'route' => 'peminjam.ajuan.create', 'aktif' => 'peminjam.ajuan.*'],
        ['label' => 'Cek Status', 'route' => 'peminjam.status.form', 'aktif' => 'peminjam.status.*'],
    ];
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-40 …">   {{-- open = menu HP terbuka/tertutup --}}
    <nav class="… rounded-full bg-white/90 shadow-lembut backdrop-blur">
```
**Cara menjelaskan:** "`x-data="{ open: false }"` adalah Alpine. Saat tombol ☰ di HP diklik, nilai `open` berubah dan menu muncul. Navbar memakai `sticky` sehingga tetap terlihat saat halaman digulir."

---

### Bagian 3b: katalog + filter, file `resources/views/peminjam/katalog/index.blade.php`

**Laci filter di HP** (Alpine, variabel `filter`):
```blade
<section id="katalog" x-data="{ filter: false }">
    {{-- Latar gelap; klik = tutup laci --}}
    <div x-show="filter" @click="filter = false" class="fixed inset-0 … md:hidden"></div>

    {{-- Sidebar: tersembunyi di HP, tampil di ≥768 px (md:block); jadi laci saat filter = true --}}
    <aside class="hidden … md:sticky md:block"
           :class="filter && '!fixed inset-y-0 left-0 z-50 !block w-[85%] …'">

    {{-- Tombol Filter khusus HP --}}
    <button type="button" @click="filter = ! filter" class="… md:hidden">Filter</button>
```

**Grid responsif** (jumlah kolom berubah per lebar layar):
```blade
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @forelse ($barang as $b)
        <x-kartu-alat :barang="$b" :sisa="$stok[$b->id]['sisa'] ?? 0" />
    @empty
        Alat tidak ditemukan.
    @endforelse
</div>
{{ $barang->links() }}   {{-- paginasi --}}
```
**Cara menjelaskan:** "Di HP ada 2 kolom. Pada layar sedang sidebar muncul sehingga kolom jadi 2 lagi, lalu 3 dan 4 di layar lebar. `md:`, `lg:`, dan `xl:` artinya 'berlaku mulai lebar layar tertentu'."

---

### Bagian 3b: kartu alat, file `resources/views/components/kartu-alat.blade.php`

```blade
@props(['barang', 'sisa' => null])

<article class="group … hover:-translate-y-1 hover:ring-2 hover:ring-magenta">
    <a href="{{ route('peminjam.katalog.show', $barang) }}">
        @if ($barang->foto)
            <img src="{{ Storage::url($barang->foto) }}" alt="{{ $barang->nama }}" loading="lazy"
                 class="aspect-square object-cover group-hover:scale-105">
        @else
            <x-ikon-alat :nama="$barang->nama" />          {{-- ikon jika belum ada foto --}}
        @endif
        @if (! is_null($sisa) && $sisa <= 0)
            <span>Habis</span>
        @endif
    </a>
    {{ $barang->nama }} · {{ $barang->kode }} · {{ $sisa }} tersedia
    <a href="{{ route('peminjam.ajuan.create', ['barang_id' => $barang->id]) }}">Pinjam</a>
</article>
```
**Cara menjelaskan:**
- Kartu ini dibuat sekali, lalu dipanggil dengan `<x-kartu-alat>`.
- Saat disentuh, kartu naik sedikit dan bergaris tepi magenta.
- `loading="lazy"` membuat foto baru dimuat ketika terlihat di layar, sehingga halaman lebih cepat.
- Tombol Pinjam langsung membuka form dengan alat yang sudah terpilih.

---

### Bagian 3b: detail alat, file `resources/views/peminjam/katalog/show.blade.php`

```blade
<a href="{{ route('peminjam.ajuan.create', ['barang_id' => $barang->id]) }}">Ajukan Peminjaman</a>
<a href="{{ route('peminjam.jadwal', ['barang_id' => $barang->id, 'tab' => 'tanggal']) }}">
    Lihat Alat Ini dalam Matriks
</a>
```

---

### Bagian 3c: matriks jadwal, file `resources/views/components/jadwal.blade.php`

Dipakai bersama oleh `peminjam/jadwal/index.blade.php` dan `admin/jadwal/index.blade.php`. Data disusun oleh `app/Services/JadwalService.php`.

```blade
@php
    $warna = [
        'dipinjam'  => 'bg-marian-800 text-white',      // biru tua
        'terlambat' => 'bg-red-600 text-white',         // merah
        'disetujui' => 'bg-marian-300 text-marian-950', // biru muda (dipesan)
        'menunggu'  => 'bg-gold text-marian-950',       // emas (khusus admin)
    ];
@endphp

{{-- Sel kosong bisa diklik → form ajuan dengan tanggal terisi --}}
<a href="{{ route('peminjam.ajuan.create', ['barang_id' => $u->barang_id, 'tanggal_pinjam' => $t->toDateString()]) }}">
```
**Cara menjelaskan:** "Satu komponen dipakai di dua halaman. Bedanya, versi publik tidak menampilkan nama peminjam (privasi) dan ajuan yang masih menunggu."

---

### Bagian 3d dan 3e: form ajuan & cek status

| Halaman | File tampilan | Validasi (server) |
|---|---|---|
| Form ajuan | `resources/views/peminjam/ajuan/create.blade.php` | `app/Http/Requests/Peminjam/AjuanRequest.php` |
| Cek status | `resources/views/peminjam/status/` | `app/Http/Controllers/Peminjam/StatusController.php` |

**Cara menjelaskan:** "Form punya `@csrf` (token keamanan) dan pesan error ditampilkan di bawah tiap kolom. Pengecekan sebenarnya dilakukan di server."

---

### Bagian 4: halaman Scan

| Bagian | File |
|---|---|
| Tampilan halaman Scan | `resources/views/admin/scan/index.blade.php` |
| Logika JavaScript | `resources/js/scan-qr.js` |
| Endpoint server | `routes/web.php` → `/admin/scan/cari`, `/admin/scan/cek-unit` |
| Pengecekan di server | `app/Services/ScanService.php`, `app/Services/PeminjamanService.php` |
| Library kamera | `public/vendor/html5-qrcode/` |

**Menghubungkan tampilan ke JS** (`admin/scan/index.blade.php`):
```blade
<div x-data="halamanScan(@js([ /* url server, mode serah/kembali */ ]))">
    …
    {{-- Kotak pesan: biru jika berhasil, merah + berkedip jika gagal --}}
    <div :class="{ 'bg-marian-800 text-white': p.jenis === 'ok',
                   'bg-red-50 text-red-800 kedip-gagal': p.jenis === 'gagal' }">
```

**1) Pengaturan dasar** (`scan-qr.js`, bagian atas):
```js
const JEDA_SCANNER_MS = 50;      // scanner USB < 30 ms per huruf; manusia > 80 ms
const MIN_PANJANG_SCAN = 4;
const MAKS_PANJANG_KODE = 100;
const JEDA_SCAN_GANDA_MS = 2500; // kode sama dalam 2,5 detik diabaikan

/** Bersihkan kode hasil scan: karakter kontrol, spasi, prefix AIM (mis. "]Q1"). */
export function bersihkanKode(kode) {
    return String(kode ?? '')
        .replace(/[\u0000-\u001F\u007F]/g, '')
        .trim()
        .replace(/^\][A-Za-z]\d/, '')
        .trim();
}
```

**2) Mengenali scanner USB**, fungsi `tanganiTombol(e)`:
```js
// Penangkap global: semua tombol keyboard diperiksa
window.addEventListener('keydown', (e) => this.tanganiTombol(e), true);

tanganiTombol(e) {
    const sekarang = performance.now();
    if (e.key.length === 1) {                    // satu huruf/angka
        const jeda = sekarang - b.waktu;
        if (jeda > 300) { b.teks = ''; b.cepat = true; }   // rangkaian baru
        else if (jeda > JEDA_SCANNER_MS) b.cepat = false;  // terlalu lambat → manusia
        b.teks += e.key;
        b.waktu = sekarang;
        return;
    }
    if (e.key === 'Enter' || e.key === 'Tab') {  // scanner mengakhiri dengan Enter
        const dariScanner = b.teks.length >= MIN_PANJANG_SCAN && b.cepat;
        if (dariScanner) this.proses(b.teks, 'scanner');
    }
}
```
**Cara menjelaskan:** "Scanner USB bekerja seperti keyboard yang mengetik sangat cepat. Kami mengukur jeda antar-huruf: kalau kurang dari 50 milidetik lalu diakhiri Enter, berarti itu hasil scan, bukan ketikan manusia."

**3) Mencegah scan ganda + antrean**, fungsi `proses()` dan `jalankanAntrean()`:
```js
proses(kodeMentah, sumber = 'manual') {
    const kode = bersihkanKode(kodeMentah);
    if (!kode) { this.tampil('gagal', 'Kode kosong…'); this.bunyi(false); return; }

    // Cegah scan ganda: kode yang sama dalam 2,5 detik diabaikan
    const sama = kode.toUpperCase() === this.terakhir.kode
              && Date.now() - this.terakhir.waktu < JEDA_SCAN_GANDA_MS;
    if (sama && sumber !== 'manual') return;

    this.terakhir = { kode: kode.toUpperCase(), waktu: Date.now() };
    this.antrean.push(kode);
    this.jalankanAntrean();
},

async jalankanAntrean() {
    if (this.sibuk) return;          // sedang memproses → tunggu giliran
    this.sibuk = true;
    while (this.antrean.length) {
        const kode = this.antrean.shift();
        if (!this.transaksi) await this.cari(kode);   // langkah 1: kode PJM
        else await this.cekUnit(kode);                // langkah 2: kode unit
    }
    this.sibuk = false;
},
```

**4) Cek unit ke server**, fungsi `cekUnit()`:
```js
async cekUnit(kode) {
    try {
        const json = await this.kirim(cfg.urlCek, {
            peminjaman_id: this.transaksi.id, kode, mode: this.mode,
            sudah: this.dipindai.map(u => u.unit_id),
        });
        this.dipindai.push({ ...json.unit, kondisi: 'baik', catatan: '' });
        this.tampil('ok', `✔ ${json.unit.kode_unit} (${json.unit.nama}) diterima.`);
        this.bunyi(true);
    } catch (e) {
        this.tampil('gagal', '✖ ' + e.message);   // pesan dari server, mis. "Jenis alat tidak sesuai ajuan"
        this.bunyi(false);
    }
},
```

**5) Tombol Simpan aktif jika lengkap:**
```js
jumlahTerscan(barangId) { return this.dipindai.filter(u => u.barang_id === barangId).length; },
lengkap() { return this.transaksi.detail.every(d => this.jumlahTerscan(d.barang_id) === d.jumlah); },
bisaSimpan() {
    return this.mode === 'serah' ? this.lengkap() : this.dipindai.length > 0;
},
```

**6) Kamera**, fungsi `nyalakanKamera()`:
```js
// Browser hanya mengizinkan kamera di HTTPS atau localhost
if (!window.isSecureContext) { this.pesanKamera = 'Kamera tidak tersedia…'; return; }

this.kamera = new window.Html5Qrcode('pembaca-qr');
await this.kamera.start(
    { facingMode: 'environment' },                 // kamera belakang
    { fps: 10, qrbox: { width: 240, height: 240 } },
    (teks) => this.proses(teks, 'kamera'),         // hasil kamera masuk antrean yang sama
);
```

**7) Bunyi beep**, fungsi `bunyi()`:
```js
osc.frequency.value = berhasil ? 880 : 220;   // nada tinggi = berhasil, nada rendah = gagal
// HP ikut bergetar bila didukung
navigator.vibrate(berhasil ? 60 : [80, 60, 80]);
```
**Cara menjelaskan:** "Tiga sumber input (kamera, scanner USB, ketik manual) semuanya masuk ke fungsi `proses()` yang sama. Jadi aturan pembersihan kode, pencegahan scan ganda, dan antreannya berlaku sama untuk semua sumber."

---

### Bagian 5: kualitas

| Poin | Bukti di kode |
|---|---|
| Responsif | class `sm:` `md:` `lg:` `xl:` di `katalog/index.blade.php`, `[font-size:clamp(…)]` untuk judul |
| Komponen dipakai ulang | `resources/views/components/` (`x-kartu-alat`, `x-jadwal`, `x-ikon-alat`) |
| Anti-XSS | `{{ $barang->nama }}` di Blade otomatis meng-escape teks |
| CSRF | `@csrf` di setiap form; `resources/js/bootstrap.js` untuk request JS |
| Cek ulang di server | `app/Services/ScanService.php`, `PeminjamanService.php`, `app/Http/Requests/` |
| Library lokal | `public/vendor/html5-qrcode/` (tidak bergantung CDN) |

---

## Cadangan: Tanya Jawab

| Pertanyaan | Jawaban |
|---|---|
| Kenapa memakai Tailwind, bukan Bootstrap? | Tailwind lebih fleksibel untuk desain khusus. Warnanya diatur terpusat di satu file, dan CSS akhirnya kecil karena class yang tidak dipakai dibuang. |
| Kenapa Alpine.js, bukan React? | Interaksinya ringan (menu, filter, scan). Alpine cukup tanpa harus membuat aplikasi front end terpisah, jadi lebih sederhana. |
| Di mana kode CSS dan JS-nya? | CSS di `resources/css/app.css` dan pengaturan tema di `tailwind.config.js`. JS di `resources/js/`, dengan logika scan di `scan-qr.js`. |
| Bagaimana jika kamera tidak bisa dipakai? | Bisa memakai scanner USB atau mengetik kode secara manual. |
| Kenapa kamera kadang tidak menyala? | Browser hanya mengizinkan kamera di HTTPS atau localhost. Di server nanti dipasang HTTPS. |
| Kenapa peminjam tidak perlu login? | Supaya praktis bagi mahasiswa. Identitas dicocokkan lewat NIM dan email, dan status dicek dengan kode PJM + NIM. |
| Kalau JavaScript diakali pengguna, apa aman? | Aman, karena semua pengecekan (ketersediaan, jenis unit, jumlah) diulang di server. |
| Kenapa disebut barcode padahal QR? | QR Code adalah barcode dua dimensi. Di menu disebut "barcode" agar akrab bagi petugas, dan QR bisa dibaca scanner 2D maupun kamera HP. |
| Bagaimana tampilan bisa responsif? | Memakai class bertingkat Tailwind (`sm:`, `md:`, `lg:`) dan grid yang menyesuaikan lebar layar. |
| Library dimuat dari CDN? | Tidak. html5-qrcode disimpan lokal di `public/vendor`, jadi tetap jalan walau internet kampus lambat. |

## Checklist Sebelum Presentasi
- [ ] Laragon menyala, web terbuka di `http://peminjaman-barang.test`
- [ ] Data contoh siap (`php artisan migrate:fresh --seed`)
- [ ] Akun admin siap login
- [ ] Siapkan 1 ajuan berstatus **Disetujui** untuk demo scan serah-terima
- [ ] Label QR unit sudah dicetak (menu Cetak Label Barcode) atau dibuka di HP lain
- [ ] HP untuk demo tampilan responsif
- [ ] Scanner USB terpasang (bila ada)
