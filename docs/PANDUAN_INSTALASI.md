# Panduan Instalasi
**Sistem Informasi Peminjaman Barang Berbasis Web dengan QR Code**
Laboratorium Kultur Jaringan, Sekolah Vokasi IPB University

Panduan ini untuk memasang aplikasi dari file **peminjaman-barang.zip** di komputer **Windows**. Perkiraan waktu: 20–30 menit (butuh koneksi internet).

---

## Langkah 1: Pasang Laragon

1. Unduh **Laragon Full** dari https://laragon.org/download, lalu pasang dengan pilihan bawaan.
2. Pastikan versinya memuat **PHP 8.1 atau lebih baru**, **MySQL**, **Composer**, dan **Node.js**. Laragon Full sudah berisi semuanya.
3. Buka Laragon, lalu klik **Start All**. Apache dan MySQL harus berwarna hijau.

---

## Langkah 2: Ekstrak ZIP

1. Klik kanan **peminjaman-barang.zip**, pilih **Extract All…**, lalu arahkan ke folder **`C:\laragon\www\`**.
2. Hasilnya harus **`C:\laragon\www\peminjaman-barang\`**, berisi folder `app`, `public`, `routes`, dan lainnya.
3. Kalau hasilnya bertingkat (`C:\laragon\www\peminjaman-barang\peminjaman-barang\`), pindahkan folder bagian dalam ke `C:\laragon\www\`.

---

## Langkah 3: Buka Terminal di folder proyek

1. Di jendela Laragon, klik tombol **Terminal**.
2. Ketik perintah berikut lalu tekan Enter:

```
cd C:\laragon\www\peminjaman-barang
```

> Semua perintah di langkah berikutnya diketik di terminal ini.

---

## Langkah 4: Pasang paket PHP

```
composer install
```

Tunggu sampai selesai (beberapa menit).

---

## Langkah 5: Pasang paket JavaScript dan build tampilan

```
npm install
```

```
npm run build
```

---

## Langkah 6: Buat file pengaturan .env

```
copy .env.example .env
```

```
php artisan key:generate
```

Buka file **`.env`** dengan Notepad, lalu pastikan bagian berikut isinya seperti ini:

```
APP_URL=http://peminjaman-barang.test
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=peminjaman_barang
DB_USERNAME=root
DB_PASSWORD=
MAIL_MAILER=log
```

Simpan file tersebut.

---

## Langkah 7: Buat database

1. Di Laragon, klik tombol **Database** (membuka HeidiSQL), lalu login dengan user `root` tanpa password.
2. Klik kanan pada koneksi, pilih **Create new → Database**.
3. Beri nama **`peminjaman_barang`**, lalu klik **OK**.

---

## Langkah 8: Isi tabel dan data contoh

```
php artisan migrate:fresh --seed
```

```
php artisan storage:link
```

- Perintah pertama membuat semua tabel beserta data contoh (alat, unit, foto, transaksi).
- Perintah kedua membuat foto alat bisa tampil.

> **Perhatian:** `migrate:fresh` menghapus semua data di database lalu mengisinya ulang. Jangan jalankan lagi bila sudah ada data asli yang ingin disimpan.

---

## Langkah 9: Buka di browser

1. Di Laragon klik **Menu → Apache → Reload**. Laragon otomatis membuat alamat `peminjaman-barang.test`.
2. Buka **http://peminjaman-barang.test**. Halaman katalog peminjam akan tampil.
3. Untuk masuk sebagai admin, klik **Login Admin** dan gunakan salah satu akun uji berikut:

| Peran | Email | Password |
|---|---|---|
| Admin | admin@peminjaman.test | password |
| Petugas (admin) | petugas@peminjaman.test | password |

> Akun di atas hanya untuk uji lokal. Ganti password bila aplikasi dipakai sungguhan.

---

## Langkah 10 (opsional): Jalankan tes

```
php artisan test
```

Hasil yang benar: semua tes berstatus **passed**.

---

## Kalau Ada Masalah

| Gejala | Solusi |
|---|---|
| `composer` / `npm` tidak dikenal | Buka terminal dari tombol **Terminal** di Laragon, jangan dari CMD biasa. |
| "could not find driver" / "Access denied" | Cek isi `.env` (DB_DATABASE, DB_USERNAME=root, DB_PASSWORD kosong) dan pastikan MySQL menyala. |
| "Unknown database" | Database `peminjaman_barang` belum dibuat (Langkah 7). |
| Tampilan berantakan tanpa warna | Belum menjalankan `npm run build` (Langkah 5). |
| Foto alat tidak muncul | Jalankan `php artisan storage:link`. |
| `peminjaman-barang.test` tidak terbuka | Reload Apache di Laragon, atau buka `http://localhost/peminjaman-barang/public`. |
| Kamera scan tidak menyala | Kamera butuh HTTPS: di Laragon klik **Menu → Apache → SSL → Enabled**, lalu buka `https://peminjaman-barang.test`. Bisa juga memakai scanner USB atau mengetik kode. |
| Email tidak terkirim | Normal, karena `MAIL_MAILER=log`. Isi email tersimpan di `storage/logs/laravel.log`. |

---

## Catatan

- Setiap komputer punya database sendiri. Data di komputer Anda **tidak tersambung** dengan komputer lain.
- Setiap menjalankan `migrate:fresh --seed`, data kembali ke data contoh yang sama.
- Bila mendapat ZIP versi baru: ekstrak ulang, lalu jalankan lagi Langkah 4, 5, dan 8.
