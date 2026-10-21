# Dokumen STLC — Sistem Informasi Peminjaman Barang Berbasis Web dengan QR Code

| Item | Keterangan |
|---|---|
| Aplikasi | Sistem Informasi Peminjaman Alat Berbasis Web dengan QR Code — Laboratorium Kultur Jaringan Sekolah Vokasi IPB University |
| Mitra | Laboratorium Kultur Jaringan, Sekolah Vokasi IPB University (`MITRA_NAMA` di `.env`) |
| Versi yang diuji | Commit setelah Sprint 5 — peminjam tanpa login & navigasi baru (`git log --oneline -1`) |
| Metode pengembangan | Agile Scrum, 5 sprint |
| Teknologi | Laravel 10, PHP 8.1, MySQL 8, Blade + Tailwind + Alpine.js, html5-qrcode, simple-qrcode, dompdf, Laravel Excel |
| Jenis pengujian | Black box (manual) + Feature Test otomatis (PHPUnit) |
| Disusun oleh | Tim pengembang (3 orang) |

---

## 1. Ringkasan Fitur

### 1.1 Per sprint

| Sprint | Fitur utama |
|---|---|
| 1 — Analisis & perancangan | Laravel 10 + Breeze; 9 tabel (users, kategori, lokasi, barang, unit_barang, peminjaman, peminjaman_detail, peminjaman_unit, pengaturan); model & relasi; seeder (1 admin, 5 peminjam, 4 kategori, 3 lokasi, 10 jenis barang, 30 unit, transaksi di 7 status); middleware `role` (403 bila peran tidak cocok, akun nonaktif dikeluarkan); layout admin (sidebar) & peminjam (navbar) responsif |
| 2 — Web dasar | CRUD kategori, lokasi, jenis barang (+ foto, kode prefix), unit barang (kode otomatis `MKR-001`, tambah 1–50 unit sekaligus), data peminjam (tambah, ubah, reset password, nonaktifkan), pengaturan aturan bisnis; larangan hapus data yang masih dipakai; pesan validasi bahasa Indonesia |
| 3 — Transaksi & laporan | Ajuan peminjaman (multi-barang, cek sisa unit langsung), verifikasi admin (setujui/tolak dengan alasan wajib), pembatalan, serah-terima, pengembalian (boleh sebagian, kondisi baik/rusak/hilang), keterlambatan & denda, riwayat, dashboard admin & beranda peminjam, laporan peminjaman/pengembalian/inventaris/keterlambatan/denda (web, PDF, Excel), notifikasi aplikasi + email, scheduler kedaluwarsa & pengingat H-1 |
| 4 — QR Code & scan | QR SVG per unit, cetak label massal A4, kartu QR peminjam bertanda tangan (HMAC), halaman Scan QR (kamera html5-qrcode lokal + input manual) untuk serah-terima & pengembalian, pencocokan hasil scan dengan ajuan |
| 5 — Hardware & pengujian | Dukungan scanner USB 2D (penangkap ketikan cepat global, auto-focus + indikator "Scanner siap", proses saat Enter/Tab, antrean, cegah scan ganda, pembersihan kode, pesan error jaringan/sesi), aturan pinjaman terlambat memblokir stok sampai unit kembali, Feature Test terfokus, dokumen STLC |
| Pasca Sprint 5 — tampilan | Desain ulang halaman publik (gaya kartu membulat, hero gradasi, kategori bento, katalog kartu) dan seluruh warna aplikasi memakai **gradasi Marian Blue (#263C92)**; data contoh diganti alat Laboratorium Kultur Jaringan SV IPB |
| Pasca Sprint 5 — perubahan konsep | **Peminjam tidak perlu login**: katalog, form ajuan (isi identitas), dan Cek Status (kode + NIM / tautan email) bersifat publik; QR kode peminjaman menggantikan kartu QR; notifikasi peminjam lewat email. Login hanya untuk admin (registrasi dihapus). **Navigasi admin** disusun ulang dengan subjudul deskripsi; **Pengaturan > Data Admin** untuk mengelola akun admin |

### 1.2 Per peran

**Admin** (login; sidebar — deskripsi tampil sebagai subjudul halaman)
| Menu | Isi | Subjudul |
|---|---|---|
| Dashboard | Ajuan menunggu, siap diambil, dipinjam, terlambat, denda bulan ini, status unit, jatuh tempo | Ringkasan inventaris dan transaksi aktif |
| Data Barang | Jenis Barang, Unit & Kondisi, Kategori, Lokasi, Cetak Label Barcode | Pengelolaan data barang, jenis, kategori, unit, kondisi, lokasi, serta barcode |
| Data Peminjam | Identitas, status aktif/nonaktif, riwayat | Pengelolaan identitas dan riwayat peminjaman setiap peminjam |
| Transaksi | Ajuan Peminjaman (badge), Peminjaman Aktif, Pengembalian, Riwayat Transaksi | Pengelolaan pengajuan, peminjaman aktif, pengembalian, dan riwayat transaksi |
| Scan Barcode | Mode serah-terima & pengembalian (kamera, scanner USB, input manual) | Pemindaian barcode untuk mencatat penyerahan dan pengembalian barang |
| Laporan | Transaksi, Pengembalian, Inventaris, Keterlambatan, Denda (PDF/Excel) | Rekapitulasi transaksi, pengembalian, inventaris, keterlambatan, dan denda |
| Pengaturan | Data Admin, Aturan & Denda | Pengelolaan data admin, aturan peminjaman, dan ketentuan denda |

**Peminjam** (TANPA login; navbar publik)
- Katalog Barang (sisa unit hari ini), Ajukan Peminjaman (isi identitas + barang), Cek Status (kode peminjaman + NIM: status, alasan penolakan, batas ambil, perkiraan denda, QR pengambilan, batalkan).
- Email: bukti ajuan terkirim (kode + tautan status), disetujui, ditolak, kedaluwarsa, pengingat H-1, pengembalian dicatat.

Label barang tetap memakai **QR Code** (dibaca scanner 2D & kamera); di menu disebut "barcode".

### 1.3 Aturan bisnis (dapat diubah di Pengaturan)

| Aturan | Nilai awal | Keterangan |
|---|---|---|
| Lama pinjam maksimal | 7 hari | Lama = rencana kembali − tanggal pinjam (tgl 1 → tgl 8 = 7 hari) |
| Maksimal unit per ajuan | 3 unit | Total semua barang dalam satu ajuan |
| Denda | Rp1.000 / unit / hari | 0 = tanpa denda; tarif dibekukan saat pengembalian dicatat |
| Batas pengambilan | 1 hari | Ajuan disetujui yang tidak diambil sampai `min(tgl pinjam + batas, rencana kembali)` → kedaluwarsa |
| Pinjaman terlambat | — | Peminjam yang punya pinjaman terlambat tidak bisa mengajukan baru |

**Rumus ketersediaan** (KetersediaanService): `sisa = kapasitas − terpakai`
- kapasitas = unit kondisi *baik* berstatus *tersedia/dipinjam*;
- terpakai = Σ jumlah ajuan *disetujui* yang bertabrakan + Σ unit belum kembali dari pinjaman *dipinjam* yang bertabrakan;
- tabrakan: `mulai_A ≤ selesai_B` dan `selesai_A ≥ mulai_B`; pinjaman yang **sudah terlambat** dianggap bertabrakan dengan semua tanggal ke depan sampai unitnya kembali;
- ajuan *menunggu* tidak mengikat stok; dicek ulang dalam `DB::transaction` + `lockForUpdate` saat admin menyetujui.

**Status peminjaman**: menunggu → disetujui / ditolak; disetujui → dipinjam / dibatalkan / kedaluwarsa; menunggu → dibatalkan; dipinjam → selesai (setelah semua unit kembali). "Terlambat" dihitung otomatis (bukan kolom).

---

## 2. Fase STLC

### 2.1 Analisis kebutuhan pengujian
Sumber kebutuhan: CLAUDE.md (alur sistem, aturan bisnis, peran, menu) dan catatan desain tiap sprint. Kebutuhan yang dapat diuji dipetakan pada **Matriks Ketertelusuran (bagian 5)**. Area berisiko tinggi:
1. Ketersediaan & overbooking (tabrakan rentang tanggal, persetujuan bersamaan).
2. Pencocokan scan (jenis, jumlah, status unit, QR tidak dikenal).
3. Perhitungan keterlambatan & denda.
4. Hak akses per peran.
5. Proses terjadwal (kedaluwarsa, pengingat H-1).

### 2.2 Perencanaan
| Item | Rencana |
|---|---|
| Strategi | (1) Feature Test otomatis untuk logika & aturan bisnis, dijalankan setiap akhir sprint; (2) uji black box manual oleh tim untuk alur UI, perangkat (kamera, scanner USB), cetak, unduh, email |
| Teknik desain kasus uji | Equivalence partitioning, boundary value analysis (lama pinjam, jumlah unit, tarif, tanggal), decision table (kondisi ↔ status unit), state transition (status peminjaman) |
| Kriteria masuk | Sprint selesai, `migrate:fresh --seed` & `php artisan test` tanpa error, aplikasi bisa dibuka |
| Kriteria keluar | 100% kasus prioritas Tinggi lulus; tidak ada cacat kritis/tinggi terbuka; seluruh Feature Test lulus |
| Kriteria tunda | Lingkungan tidak tersedia (DB/MySQL mati), cacat kritis menghambat > 30% kasus |
| Peran | Penguji 1: Admin & Data Barang; Penguji 2: Transaksi, Laporan, Scheduler; Penguji 3: Peminjam, QR/Scan, perangkat |

### 2.3 Desain kasus uji
Lihat **bagian 4**. Setiap kasus memiliki ID, skenario, langkah/data uji, hasil yang diharapkan, jenis (P = positif, N = negatif/input salah, B = batas), rujukan Feature Test (bila ada), kolom hasil aktual dan status (Lulus/Gagal/Tertunda).

### 2.4 Lingkungan uji
| Komponen | Spesifikasi |
|---|---|
| Server lokal | Windows + Laragon, PHP 8.1, MySQL 8.0, Node.js 18 |
| URL | `http://localhost:8000` (`php artisan serve`) atau `https://peminjaman-barang.test` (SSL Laragon, wajib untuk kamera dari HP) |
| Database | `peminjaman_barang` (manual), `peminjaman_barang_test` (Feature Test) |
| Email | `MAIL_MAILER=log` → `storage/logs/laravel.log` |
| Browser | Chrome/Edge terbaru (desktop), Chrome Android (kamera) |
| Perangkat | Webcam/kamera HP, scanner USB 2D (mode keyboard, akhiran Enter) |
| Persiapan setiap putaran | `php artisan migrate:fresh --seed`, `npm run build` |

### 2.5 Eksekusi
1. Jalankan tes otomatis: `php artisan test` → catat jumlah lulus/gagal.
2. Jalankan kasus uji manual bagian 4 sesuai pembagian penguji; isi kolom *Hasil aktual* dan *Status*.
3. Cacat dicatat di **Log Cacat (bagian 8)**, diperbaiki, lalu diuji ulang (retest) + regresi `php artisan test`.

### 2.6 Penutupan
Isi **Laporan Penutupan (bagian 9)**: jumlah kasus, persentase lulus, cacat per tingkat, catatan risiko, keputusan rilis.

---

## 3. Data Uji (hasil `php artisan migrate:fresh --seed`)

Akun **admin** memakai password **`password`**. Peminjam **tidak punya akun login** — data di bawah dipakai sebagai identitas (NIM + email) di form ajuan dan Cek Status. Tanggal relatif terhadap hari seeder dijalankan (H = hari ini).

| Email / NIM | Peran | Data penting |
|---|---|---|
| admin@peminjaman.test | Admin (login) | — |
| petugas@peminjaman.test | Admin (login) | — |
| andi@peminjaman.test | Peminjam (J0301201001) | Ajuan **menunggu** Mikroskop Stereo×1 + Set Erlenmeyer×1 (H+2 s/d H+4); riwayat **selesai** (MKR-004 terlambat 1 hari Rp1.000, STR-003 kembali rusak) |
| budi@peminjaman.test | Peminjam (J0301201002) | Ajuan **disetujui** Timbangan Analitik×1 + Bunsen Burner×1 (H s/d H+2); ajuan **dibatalkan** |
| citra@peminjaman.test | Peminjam (J0302201003) | Ajuan **ditolak** (dengan alasan); ajuan **kedaluwarsa** |
| dewi@peminjaman.test | Peminjam (J0303201004) | **Dipinjam** CTP-001, CTP-002 (rencana kembali H+2) |
| eko@peminjaman.test | Peminjam (J0304201005) | **Dipinjam & terlambat** PHM-001 (rencana kembali H−2) |

Unit khusus: `HPS-003` rusak/perbaikan, `MPP-002` hilang, `STR-003` rusak/perbaikan. Jenis barang & kode: Mikroskop Stereo MKR(4), pH Meter PHM(4), Hot Plate Stirrer HPS(3), Timbangan Analitik TBA(3), Bunsen Burner BNS(3), Set Pinset & Scalpel PSN(3), Mikropipet MPP(2), Set Cawan Petri CTP(3), Set Erlenmeyer ERL(2), Bead Sterilizer STR(3).

---

## 4. Daftar Kasus Uji

Kolom: **J** = jenis (P positif, N negatif/input salah, B nilai batas); **Tes otomatis** = kelas Feature Test yang mencakup logika yang sama (`—` = hanya manual). Prioritas: kasus bertanda ★ adalah prioritas Tinggi.

### 4.1 Autentikasi, Hak Akses & Navigasi (AU)
Prasyarat: seed baru. Penguji: ________ Tanggal: ________

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| AU-01 ★ | Login admin | admin@peminjaman.test / password | Masuk ke /admin/dashboard dengan sidebar | P | AuthenticationTest, RoleAccessTest | | |
| AU-02 ★ | Halaman peminjam tanpa login | Buka /, /ajuan, /status sebagai tamu | Semua terbuka tanpa diminta login | P | RoleAccessTest | | |
| AU-03 ★ | Peminjam mencoba login | andi@peminjaman.test / password | Ditolak "Email atau password salah." | N | RoleAccessTest, PeminjamPengaturanTest | | |
| AU-04 | Password admin salah | admin@… / salah123 | Ditolak, tetap di login | N | AuthenticationTest | | |
| AU-05 | Login berulang gagal | 6× password salah | "Terlalu banyak percobaan…" | N | — | | |
| AU-06 ★ | Admin nonaktif login | Nonaktifkan petugas@ (Data Admin), login petugas@ | "Akun Anda dinonaktifkan. Hubungi admin." | N | RoleAccessTest | | |
| AU-07 | Admin dinonaktifkan saat sedang login | Petugas login; admin menonaktifkan; petugas klik menu | Dikeluarkan ke halaman login | N | RoleAccessTest | | |
| AU-08 | Registrasi dihapus | Buka /register | 404 | N | RoleAccessTest | | |
| AU-09 ★ | Tamu buka panel admin | /admin/dashboard, /admin/barang, /profile | Diarahkan ke login | N | RoleAccessTest | | |
| AU-10 | Lupa password admin | Forgot password → admin@… | Tautan reset tercatat di log email | P | PasswordResetTest, RoleAccessTest | | |
| AU-11 | Lupa password dengan email peminjam | Forgot password → andi@… | Ditolak; tidak ada email reset | N | RoleAccessTest | | |
| AU-12 | Ubah profil & ganti password admin | Avatar → Profil / Ganti Password | Tersimpan | P | ProfileTest, PasswordUpdateTest | | |
| AU-13 | Logout | Avatar → Keluar | Kembali ke login | P | AuthenticationTest | | |
| AU-14 ★ | Urutan sidebar | Login admin | Dashboard, Data Barang, Data Peminjam, Transaksi, Scan Barcode, Laporan, Pengaturan | P | — | | |
| AU-15 ★ | Subjudul halaman | Buka tiap menu | Header menampilkan nama menu + deskripsi (mis. Dashboard: "Ringkasan inventaris dan transaksi aktif") | P | RoleAccessTest, AkunAdminTest | | |
| AU-16 | Menu aktif & responsif | Buka submenu; perkecil < 640 px | Menu aktif ditandai, grup terbuka; sidebar jadi ☰; navbar publik jadi menu mobile | P | — | | |
| AU-18 | Tampilan responsif publik | Buka beranda, form ajuan, status di lebar 360 px, 768 px, 1280 px | Tidak ada scroll horizontal; kartu menyusun ulang otomatis (auto-fit); teks tetap terbaca | P | — | | |
| AU-19 | Konsistensi warna | Telusuri halaman publik & admin | Seluruh aksen memakai gradasi biru Marian; merah hanya untuk pesan gagal/ditolak | P | — | | |
| AU-17 | Tautan antar-area | Navbar publik "Login Admin"; sidebar "Lihat situs"; navbar publik saat admin login | Login / katalog / "Dashboard Admin" | P | — | | |
### 4.2 Kategori (KT)
Prasyarat: login admin. Penguji: ________ Tanggal: ________

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| KT-01 | Tambah kategori | "Alat Olahraga" | Tersimpan, pesan sukses | P | KategoriLokasiTest | | |
| KT-02 | Nama kosong | "" | "Nama kategori wajib diisi." | N | KategoriLokasiTest | | |
| KT-03 | Nama duplikat | "Alat Ukur & Analisis" | "Nama kategori sudah digunakan." | N | KategoriLokasiTest | | |
| KT-04 | Nama terlalu panjang | 101 karakter | Ditolak (maks 100) | B | KategoriLokasiTest | | |
| KT-05 | Simpan ulang tanpa ubah nama | Edit "Peralatan Inokulasi" → Simpan | Tersimpan, tidak dianggap duplikat | P | KategoriLokasiTest | | |
| KT-06 ★ | Hapus kategori yang dipakai | Hapus "Alat Ukur & Analisis" | Ditolak, pesan merah | N | KategoriLokasiTest | | |
| KT-07 | Hapus kategori kosong | Hapus KT-01 | Terhapus | P | KategoriLokasiTest | | |
| KT-08 | Cari | "gelas" | Hanya Wadah & Gelas Kultur | P | — | | |

### 4.3 Lokasi (LK)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| LK-01 | Tambah lokasi | "Ruang Rapat", "Lt. 4" | Tersimpan | P | KategoriLokasiTest | | |
| LK-02 | Nama kosong / duplikat | "" / "Gudang Alat" | Ditolak | N | KategoriLokasiTest | | |
| LK-03 | Keterangan > 255 | 256 karakter | Ditolak | B | — | | |
| LK-04 ★ | Hapus lokasi berisi unit | Hapus "Gudang Alat" | Ditolak | N | KategoriLokasiTest | | |
| LK-05 | Jumlah unit bisa diklik | Klik angka unit | Daftar unit terfilter lokasi | P | — | | |

### 4.4 Jenis Barang (BR)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| BR-01 | Tambah barang + foto | "Printer", kode "prt", foto .jpg 500 KB | Tersimpan, kode "PRT", foto tampil | P | BarangUnitTest | | |
| BR-02 | Field wajib kosong | nama/kode/kategori kosong | Ketiganya ditolak | N | BarangUnitTest | | |
| BR-03 | Kode duplikat | "MKR" | Ditolak | N | BarangUnitTest | | |
| BR-04 | Format kode salah | "P1", "A", "ABCDEF" | Ditolak: 2–5 huruf | B | BarangUnitTest | | |
| BR-05 | Foto bukan gambar | .pdf | Ditolak | N | BarangUnitTest | | |
| BR-06 | Foto > 2 MB | .jpg 3 MB | Ditolak | B | BarangUnitTest | | |
| BR-07 ★ | Ubah kode barang yang sudah punya unit | MKR → PRY | Ditolak, kode terkunci | N | BarangUnitTest | | |
| BR-08 | Hapus foto | Centang "Hapus foto" | Foto hilang | P | — | | |
| BR-09 ★ | Hapus barang yang punya unit | Hapus Mikroskop Stereo | Ditolak | N | BarangUnitTest | | |
| BR-10 | Hapus barang tanpa unit | Hapus Printer | Terhapus | P | — | | |
| BR-11 | Filter + cari | Kategori Peralatan Inokulasi, "bun" | Hanya Bunsen Burner | P | — | | |

### 4.5 Unit Barang (UN)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| UN-01 ★ | Tambah 1 unit | Mikroskop Stereo, jumlah 1 | `MKR-005`, tersedia | P | BarangUnitTest | | |
| UN-02 ★ | Tambah banyak unit | Mikroskop Stereo, jumlah 3 | `MKR-006`–`MKR-008`, pesan menyebut rentang | P | BarangUnitTest | | |
| UN-03 | Pratinjau kode | Pilih jenis barang | Kode berikutnya tampil | P | BarangUnitTest | | |
| UN-04 | Nomor setelah unit dihapus | Hapus unit terakhir tanpa riwayat, tambah 1 | Lanjut dari nomor terbesar yang ada | B | BarangUnitTest | | |
| UN-05 | Nomor > 999 | (data uji) MKR-999 lalu tambah 2 | MKR-1000, MKR-1001 | B | BarangUnitTest | | |
| UN-06 | Jumlah di luar batas | 0, 51, -1, "abc" | Ditolak 1–50 | B | BarangUnitTest | | |
| UN-07 | Tanggal perolehan masa depan | Besok | Ditolak | N | BarangUnitTest | | |
| UN-08 | Unit baru kondisi rusak | Kondisi rusak | Status otomatis perbaikan | P | BarangUnitTest | | |
| UN-09 ★ | Kombinasi kondisi/status salah | Rusak + tersedia; hilang + perbaikan | Ditolak | N | BarangUnitTest | | |
| UN-10 ★ | Ubah unit sedang dipinjam | Edit CTP-001 | Form terkunci; paksa kirim ditolak | N | BarangUnitTest | | |
| UN-11 ★ | Hapus unit yang punya riwayat | Hapus MKR-004 | Ditolak | N | BarangUnitTest | | |
| UN-12 | Hapus unit tanpa riwayat | Hapus unit UN-01 | Terhapus | P | BarangUnitTest | | |
| UN-13 | Filter status | Status perbaikan | HPS-003, STR-003 | P | — | | |

### 4.6 Data Peminjam (PJ)
Peminjam tidak punya akun login; datanya terbentuk dari form ajuan publik atau ditambah admin.

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| PJ-01 | Tambah peminjam oleh admin | Nama, NIM, email, no. HP | Tersimpan sebagai peminjam tanpa password | P | PeminjamPengaturanTest | | |
| PJ-02 | Email / NIM duplikat | andi@… / J0301201001 | Ditolak | N | PeminjamPengaturanTest | | |
| PJ-03 | Email format salah | "andi@" | Ditolak | N | PeminjamPengaturanTest | | |
| PJ-04 | No. HP berisi huruf / NIM kosong | "08abc" / "" | Ditolak | N | PeminjamPengaturanTest | | |
| PJ-05 | Ubah kontak peminjam | Ubah No. HP Andi | Tersimpan; dipakai di ajuan berikutnya | P | PeminjamPengaturanTest | | |
| PJ-06 ★ | Nonaktifkan peminjam | Status nonaktif, lalu ajukan dengan NIM itu | Ajuan ditolak "dinonaktifkan" | N | PeminjamPengaturanTest, AjuanVerifikasiTest | | |
| PJ-07 | Peminjam dari form publik | Ajukan dengan NIM baru | Muncul di Data Peminjam beserta riwayatnya | P | AjuanVerifikasiTest | | |
| PJ-08 ★ | Hapus peminjam dengan riwayat | Hapus Andi | Ditolak, saran nonaktifkan | N | PeminjamPengaturanTest | | |
| PJ-09 ★ | Ubah akun admin lewat URL peminjam | /admin/peminjam/{id admin}/edit | 404 | N | PeminjamPengaturanTest | | |
| PJ-10 | Detail peminjam | Klik Andi | Identitas + 10 transaksi terakhir | P | PeminjamPengaturanTest | | |
### 4.7 Pengaturan (PG)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| PG-01 | Ubah nilai | 14 / 5 / 0 / 2 | Tersimpan | P | PeminjamPengaturanTest | | |
| PG-02 | Lama pinjam di luar batas | 0, 91 | Ditolak (1–90) | B | PeminjamPengaturanTest | | |
| PG-03 | Denda negatif | -500 | Ditolak | B | PeminjamPengaturanTest | | |
| PG-04 | Bukan angka / kosong | "tiga", "" | Ditolak, nilai lama tetap | N | PeminjamPengaturanTest | | |
| PG-05 ★ | Pengaturan berlaku di ajuan | Lama maks 2 → ajukan 3 hari | Ditolak "maksimal 2 hari" | P | AjuanVerifikasiTest | | |

### 4.8 Ajuan Peminjaman (AJ) — halaman publik
Prasyarat: **tanpa login** (tamu), buka menu Ajukan Peminjaman. Identitas uji: data peminjam seeder atau NIM baru. Penguji: ________ Tanggal: ________

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|| AJ-01 ★ | Ajuan valid tanpa login | Identitas Andi, pH Meter×1, besok s/d lusa | Diarahkan ke halaman status: kode PJM-YYYYMMDD-NNNN + QR; email bukti ke peminjam & email ke admin | P | AjuanVerifikasiTest | | |
| AJ-02 | Kode berurutan per hari | Dua ajuan hari ini | Nomor urut naik | P | AjuanVerifikasiTest | | |
| AJ-03 | Tanggal pinjam kemarin | (inspect element) | Ditolak | N | AjuanVerifikasiTest | | |
| AJ-04 | Kembali < pinjam | tgl 20 → tgl 19 | Ditolak | N | AjuanVerifikasiTest | | |
| AJ-05 ★ | Lama > maks | tgl 1 → tgl 9 | Ditolak "maksimal 7 hari" | B | AjuanVerifikasiTest | | |
| AJ-06 ★ | Lama = maks | tgl 1 → tgl 8 | Diterima | B | AjuanVerifikasiTest | | |
| AJ-07 | Pinjam & kembali hari sama | H → H | Diterima | B | AjuanVerifikasiTest | | |
| AJ-08 ★ | Total unit > maks | pH Meter 2 + Bunsen Burner 2 | Ditolak "maksimal 3 unit" | B | AjuanVerifikasiTest | | |
| AJ-09 | Barang sama dua baris | Mikroskop Stereo 1 + 1 | Ditolak "gabungkan jumlahnya" | N | AjuanVerifikasiTest | | |
| AJ-10 | Jumlah 0 / tanpa barang / tujuan kosong | — | Ditolak | N | AjuanVerifikasiTest | | |
| AJ-11 ★ | Stok kurang pada rentang | Timbangan Analitik×3, H s/d H+1 | Peringatan merah "Sisa 2"; server menolak | N | AjuanVerifikasiTest | | |
| AJ-12 | Rentang tidak bertabrakan | Timbangan Analitik×3 setelah H+2 | Diterima | P | AjuanVerifikasiTest | | |
| AJ-13 | Ajuan menunggu tidak mengikat stok | Dua akun ajukan Timbangan Analitik×3 tanggal sama | Keduanya diterima sebagai menunggu | P | KetersediaanTest | | |
| AJ-14 ★ | Peminjam terlambat mengajukan | NIM Eko (J0304201005) + eko@… | Ditolak di kolom NIM: masih punya pinjaman terlambat | N | AjuanVerifikasiTest | | |
| AJ-15 | Katalog | Katalog → cari, filter kategori | Sisa unit hari ini tampil; tombol Pinjam mengisi barang | P | SchedulerNotifikasiLaporanTest | | |
| AJ-16 ★ | NIM baru | NIM 2301777, email baru | Data peminjam baru dibuat (tanpa password) | P | AjuanVerifikasiTest | | |
| AJ-17 ★ | NIM terdaftar, email berbeda | NIM J0301201001 + email lain | Ditolak; email tersimpan disamarkan (a***i@…) | N | AjuanVerifikasiTest | | |
| AJ-18 | Email dipakai NIM lain | NIM baru + andi@peminjaman.test | Ditolak | N | AjuanVerifikasiTest | | |
| AJ-19 | Nama berbeda untuk NIM terdaftar | NIM Andi, nama lain, no. HP baru | Diterima; nama tersimpan tetap, no. HP diperbarui | P | AjuanVerifikasiTest | | |
| AJ-20 | Validasi identitas | Nama/NIM kosong, email salah, HP "abc", NIM berisi `<script>` | Ditolak per kolom | N | AjuanVerifikasiTest | | |
| AJ-21 | Jebakan bot | Isi kolom tersembunyi "website" (inspect element) | Ditolak | N | AjuanVerifikasiTest | | |
| AJ-22 | Pembatasan kiriman | Kirim > 10 kali dalam 1 menit | "Too Many Requests" (429) | N | — | | |

### 4.9 Ketersediaan (KS) — logika inti
Diuji lewat form ajuan (sisa unit) dan panel verifikasi admin.

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| KS-01 ★ | Tabrakan bersinggungan di awal/akhir | Ajuan disetujui tgl 12–14 (2 unit dari 3); cek tgl 10–12 dan 14–20 | Sisa 1 | B | KetersediaanTest | | |
| KS-02 | Tidak bertabrakan | Cek tgl 10–11 dan 15–16 | Sisa 3 | B | KetersediaanTest | | |
| KS-03 | Beberapa ajuan dijumlahkan | 2 + 1 unit bertabrakan di tgl 13 | Sisa 5 − 3 = 2 | P | KetersediaanServiceTest | | |
| KS-04 | Status lain tidak dihitung | Ajuan ditolak/dibatalkan/kedaluwarsa/selesai | Stok tidak berkurang | P | KetersediaanTest | | |
| KS-05 ★ | Unit rusak/perbaikan/hilang | Ubah unit ke perbaikan | Kapasitas berkurang | P | KetersediaanServiceTest | | |
| KS-06 ★ | Pinjaman terlambat memblokir ke depan | Satu-satunya unit dipinjam & terlambat; ajukan untuk minggu depan | Sisa 0 sampai unit dikembalikan | N | KetersediaanServiceTest, PersetujuanAjuanTest | | |
| KS-07 | Pengembalian sebagian membebaskan unit | Kembalikan 1 dari 2 unit | Sisa bertambah 1 | P | KetersediaanTest | | |
| KS-08 | Uji acak | 200 skenario dibandingkan dengan hitungan manual | Semua sama | P | KetersediaanServiceTest | | |

### 4.10 Verifikasi Ajuan (VR)
Prasyarat: login admin.

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| VR-01 | Panel verifikasi | Buka ajuan menunggu Andi | Cek akun, NIM, keterlambatan, tabel sisa stok | P | AjuanVerifikasiTest | | |
| VR-02 ★ | Setujui | Klik Setujui | Status disetujui, verifikator tercatat, notifikasi + email ke peminjam | P | AjuanVerifikasiTest | | |
| VR-03 ★ | Cegah overbooking | Dua ajuan Timbangan Analitik×2 bertabrakan; setujui keduanya | Pertama sukses, kedua "Stok tidak cukup…" | N | AjuanVerifikasiTest, PersetujuanAjuanTest | | |
| VR-04 ★ | Persetujuan atomik | Ajuan Mikroskop Stereo×1 + Mikropipet×2 (sisa 1) | Gagal; status tetap menunggu; tidak ada notifikasi | N | PersetujuanAjuanTest | | |
| VR-05 | Setujui dua kali | Klik Setujui lagi (tab lain) | "status ajuan sekarang disetujui" | N | PersetujuanAjuanTest | | |
| VR-06 ★ | Tolak tanpa alasan / alasan pendek / spasi saja | "", "pendek", "     " | Ditolak, form tolak tetap terbuka | N | AjuanVerifikasiTest, PersetujuanAjuanTest | | |
| VR-07 ★ | Tolak dengan alasan | "Barang dipakai untuk ujian praktik." | Status ditolak, alasan tampil di peminjam, notifikasi | P | AjuanVerifikasiTest | | |
| VR-08 | Verifikasi ajuan yang dibatalkan | POST setujui | Ditolak | N | AjuanVerifikasiTest | | |
| VR-09 | Badge ajuan | Setelah VR-02 | Badge berkurang 1 | P | — | | |
| VR-10 | Pembatalan mengembalikan stok | Setujui A (habiskan stok), A dibatalkan, setujui B | B berhasil | P | PersetujuanAjuanTest | | |

### 4.11 Cek Status & Pembatalan (BT) — tanpa login

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| BT-01 ★ | Cek status kode + NIM | Cek Status → kode ajuan Citra (ditolak) + J0302201003 | Status ditolak + alasan penolakan | P | AjuanVerifikasiTest | | |
| BT-02 ★ | NIM / kode salah | Kode benar + NIM lain; kode tidak ada | "Kode peminjaman atau nomor identitas tidak cocok." (pesan sama) | N | AjuanVerifikasiTest | | |
| BT-03 | Buka /status/{kode} langsung tanpa akses | Ketik URL status orang lain | Diarahkan ke form Cek Status | N | AjuanVerifikasiTest | | |
| BT-04 ★ | Tautan dari email | Klik "Lihat Detail" di email (log) | Halaman status terbuka tanpa login | P | AjuanVerifikasiTest, SchedulerNotifikasiLaporanTest | | |
| BT-05 | Tautan email diubah | Ubah nilai `signature` | Diarahkan ke form Cek Status | N | AjuanVerifikasiTest | | |
| BT-06 ★ | Batalkan ajuan menunggu | Status Andi → Batalkan | Status dibatalkan | P | AjuanVerifikasiTest | | |
| BT-07 | Batalkan ajuan disetujui | Status Budi → Batalkan | Status dibatalkan, stok kembali | P | AjuanVerifikasiTest, PersetujuanAjuanTest | | |
| BT-08 ★ | Batalkan yang sudah dipinjam | Status Dewi | Tombol tidak ada; paksa POST → ditolak | N | AjuanVerifikasiTest | | |
| BT-09 ★ | Batalkan tanpa akses | POST /status/{kode orang lain}/batal | 403 | N | AjuanVerifikasiTest | | |
| BT-10 | Cetak bukti | Halaman status → Cetak bukti | Hanya isi status & QR tercetak | P | — | | |
| BT-11 | Pembatasan cek status | > 10 percobaan / menit | 429 | N | — | | |
### 4.12 Serah-terima lewat form (ST)
Prasyarat: login admin; ajuan Budi disetujui.

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| ST-01 ★ | Serah-terima lengkap | Centang TBA-001, BNS-001 | Status dipinjam, unit dipinjam | P | SerahPengembalianTest | | |
| ST-02 | Ketik kode manual | "kmr-001, trp-001" | Diterima | P | SerahPengembalianTest | | |
| ST-03 ★ | Jenis salah | PHM-002 | "tidak termasuk dalam ajuan" | N | SerahPengembalianTest | | |
| ST-04 ★ | Jumlah kurang / lebih | TBA-001 saja / TBA-001+002+BNS-001 | "belum lengkap" / "sudah cukup" | N | SerahPengembalianTest | | |
| ST-05 | Unit rusak / dipinjam orang lain | HPS-003 / CTP-001 | "tidak bisa dipinjam" | N | SerahPengembalianTest | | |
| ST-06 | Kode tidak dikenal | XYZ-999 | "Kode unit tidak dikenal" | N | SerahPengembalianTest | | |
| ST-07 | Sebelum tanggal pinjam | Ajuan Andi (H+2) setelah disetujui | Peringatan; tombol nonaktif | N | SerahPengembalianTest | | |
| ST-08 | Lewat batas ambil | (data uji) ajuan disetujui tgl pinjam H−2 | "Batas waktu pengambilan sudah lewat" | N | SerahPengembalianTest | | |

### 4.13 Pengembalian & Denda (PB)
Prasyarat: login admin.

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| PB-01 ★ | Tepat waktu | Dewi, semua baik | Denda Rp0, status selesai, unit tersedia | P | SerahPengembalianTest | | |
| PB-02 ★ | Terlambat | Eko (PHM-001, terlambat 2 hari) | Denda Rp2.000 | P | SerahPengembalianTest | | |
| PB-03 ★ | Denda per unit | 2 unit terlambat 3 hari | Rp6.000 | P | SerahPengembalianTest | | |
| PB-04 ★ | Pengembalian sebagian | Dewi: CTP-001 saja | Tetap dipinjam; berikutnya hanya CTP-002 | P | SerahPengembalianTest | | |
| PB-05 ★ | Kondisi rusak / hilang | Pilih rusak / hilang | Unit → perbaikan / hilang, tidak bisa dipinjam | P | SerahPengembalianTest | | |
| PB-06 | Unit hilang tetap kena denda | Terlambat 2 hari, kondisi hilang | Denda Rp2.000 | P | PerhitunganDendaTest | | |
| PB-07 | Tanggal kembali masa depan | Besok | Ditolak | N | SerahPengembalianTest | | |
| PB-08 | Tanggal sebelum serah-terima | Sebelum tgl diserahkan | Ditolak | N | SerahPengembalianTest | | |
| PB-09 | Tanggal mundur | Rencana kemarin, dicatat dengan tanggal kemarin | Terlambat 0 | B | SerahPengembalianTest | | |
| PB-10 | Tidak ada unit dicentang | — | Ditolak | N | SerahPengembalianTest | | |
| PB-11 ★ | Tarif berubah setelah dicatat | Ubah tarif 5000 setelah PB-02 | Denda PB-02 tetap | P | SerahPengembalianTest, PerhitunganDendaTest | | |
| PB-12 | Tarif berubah di antara dua pengembalian sebagian | Unit 1 @1000; tarif 3000; unit 2 besok | Rp2.000 + Rp9.000 | P | PerhitunganDendaTest | | |
| PB-13 | Tarif 0 | Pengaturan denda 0 | Hari terlambat tercatat, denda Rp0 | B | SerahPengembalianTest | | |
| PB-14 | Kalender | Lintas bulan/tahun, 29 Feb kabisat | Hari terlambat benar | B | PerhitunganDendaTest | | |
| PB-15 | Kembali di hari rencana pukul 23:50 | — | Tidak terlambat | B | PerhitunganDendaTest | | |
| PB-16 | Perkiraan denda berjalan | Eko buka detail pinjaman | Kotak merah perkiraan denda | P | PerhitunganDendaTest | | |

### 4.14 Riwayat, Notifikasi Email, Dashboard (RN)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| RN-01 | Tanda terlambat | Peminjaman Aktif → tab Terlambat | Eko dengan badge "+2 hari" | P | SchedulerNotifikasiLaporanTest | | |
| RN-02 | Filter riwayat | Status selesai; rentang tanggal; cari NIM | Hanya data cocok | P | SchedulerNotifikasiLaporanTest | | |
| RN-03 | Rentang riwayat terbalik | Dari > sampai | Pesan validasi | N | — | | |
| RN-04 ★ | Email bukti ajuan | Kirim ajuan (AJ-01) | Log: "Ajuan peminjaman terkirim — PJM-…" ke email peminjam | P | AjuanVerifikasiTest | | |
| RN-05 ★ | Email disetujui / ditolak | Admin verifikasi | Email ke peminjam berisi batas ambil / alasan + tautan status | P | AjuanVerifikasiTest | | |
| RN-06 | Email ke admin | Ajuan baru | Semua admin aktif menerima email "Ajuan peminjaman baru" berisi tautan ke panel admin | P | SchedulerNotifikasiLaporanTest | | |
| RN-07 | Email kedaluwarsa, pengingat H-1, pengembalian | Jalankan scheduler / catat pengembalian | Email terkirim dengan tautan status | P | SchedulerNotifikasiLaporanTest | | |
| RN-08 | Dashboard admin | Buka dashboard | Angka sesuai; kartu bisa diklik | P | SchedulerNotifikasiLaporanTest | | |
| RN-09 | Halaman status peminjam terlambat | Cek status pinjaman Eko | Kotak merah perkiraan denda | P | PerhitunganDendaTest | | |
### 4.15 Laporan (LP)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| LP-01 | Lima jenis laporan | Peminjaman, Pengembalian, Inventaris, Keterlambatan, Denda | Tabel & ringkasan tampil | P | SchedulerNotifikasiLaporanTest | | |
| LP-02 ★ | Unduh PDF | Klik Unduh PDF | File .pdf berkop mitra, landscape, tanda tangan petugas | P | SchedulerNotifikasiLaporanTest | | |
| LP-03 ★ | Unduh Excel | Klik Unduh Excel | File .xlsx: kop, header, data, ringkasan | P | SchedulerNotifikasiLaporanTest | | |
| LP-04 | Periode tanpa data | Rentang kosong | "Tidak ada data pada periode ini." | B | — | | |
| LP-05 | Rentang terbalik | Dari > sampai | Pesan validasi | N | SchedulerNotifikasiLaporanTest | | |
| LP-06 | Keterlambatan berjalan | Ada pinjaman terlambat | Baris "Belum kembali", denda "(perkiraan)" | P | SchedulerNotifikasiLaporanTest | | |
| LP-07 ★ | Total denda cocok | Bandingkan total di laporan denda dengan detail transaksi | Sama | P | PerhitunganDendaTest | | |
| LP-08 | Jenis tidak ada / akses peminjam | /admin/laporan/xyz; peminjam buka laporan | 404 / 403 | N | SchedulerNotifikasiLaporanTest | | |

### 4.16 Scheduler (SC) — lihat panduan bagian 6

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| SC-01 | Jadwal terdaftar | `php artisan schedule:list` | kedaluwarsa 00:05, pengingat 07:00 | P | SchedulerNotifikasiLaporanTest | | |
| SC-02 | Masih dalam batas ambil | `php artisan peminjaman:kedaluwarsa` | 0 ajuan | B | SchedulerNotifikasiLaporanTest | | |
| SC-03 ★ | Lewat batas ambil | `--tanggal=H+2` | Ajuan Budi kedaluwarsa + notifikasi | P | SchedulerNotifikasiLaporanTest | | |
| SC-04 | Dijalankan ulang | Ulangi SC-03 | 0 ajuan (tidak diproses ulang) | P | SchedulerNotifikasiLaporanTest | | |
| SC-05 | Batas ambil dari pengaturan | Batas 3 hari | Belum kedaluwarsa | B | SchedulerNotifikasiLaporanTest | | |
| SC-06 ★ | Pengingat H-1 | `peminjaman:pengingat --tanggal=H+1` | 1 pengingat ke Dewi | P | SchedulerNotifikasiLaporanTest | | |
| SC-07 | Pengingat tidak dobel | Ulangi SC-06 | 0 pengingat | P | SchedulerNotifikasiLaporanTest | | |

### 4.17 QR Unit & Label (QR)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| QR-01 | Unduh QR unit | Unit Barang → "QR" MKR-001 | qr-MKR-001.svg; di-scan HP berisi "MKR-001" | P | QrLabelTest | | |
| QR-02 | Label per jenis | Detail Mikroskop Stereo → Cetak Label Barcode | Label MKR-001…004 (QR, kode, nama, mitra) | P | QrLabelTest | | |
| QR-03 | Label sesuai filter | Filter lokasi → Cetak Label (sesuai filter) | Hanya unit lokasi itu | P | QrLabelTest | | |
| QR-04 | Label terpilih | Centang 3 unit → Cetak label terpilih | Tepat 3 label; tombol nonaktif bila 0 | P | QrLabelTest | | |
| QR-05 | Ukuran label | 3 / 4 / 5 per baris | Grid berubah; `?kolom=9` ditolak | B | QrLabelTest | | |
| QR-06 ★ | Hasil cetak | Ctrl+P | Hanya label, tidak terpotong; QR cetakan terbaca scanner | P | — | | |

### 4.18 QR Kode Peminjaman (KQ) — pengganti kartu QR peminjam

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| KQ-01 | QR di halaman status | Setelah AJ-01 / Cek Status | QR + kode PJM + nama peminjam tampil | P | AjuanVerifikasiTest | | |
| KQ-02 ★ | QR membuka transaksi | Admin Scan Barcode (mode serah) → scan QR di layar HP peminjam | Ajuan terbuka | P | ScanTest | | |
| KQ-03 | QR ajuan yang belum disetujui | Scan QR ajuan menunggu | "…berstatus "menunggu", bukan "disetujui"." | N | ScanTest | | |
| KQ-04 | Kartu QR lama (PMJ.…) | `PMJ.1.0000000000` | "QR tidak dikenal: PMJ.1.0000000000." (kartu peminjam sudah dihapus) | N | ScanTest, PencocokanScanTest | | |
### 4.19 Scan — Serah-terima (SS)
Prasyarat: admin → Scan Barcode → mode Serah-terima; ajuan Budi (Timbangan Analitik×1, Bunsen Burner×1). Input lewat kamera, scanner USB, atau ketik + Enter.

| ID | Skenario | Input | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| SS-01 ★ | Buka dengan QR/kode peminjaman | QR di halaman status Budi atau ketik PJM-… | Transaksi terbuka, Timbangan Analitik 0/1, Bunsen Burner 0/1 | P | ScanTest | | |
| SS-02 | Kode unit sebelum transaksi | TBA-001 | "…Scan QR kode peminjaman terlebih dahulu." | N | ScanTest | | |
| SS-03 ★ | **QR tidak dikenal** (buka transaksi) | ABC-123 | "QR tidak dikenal: ABC-123." + bunyi gagal + kotak merah berkedip | N | ScanTest | | |
| SS-04 | Kode peminjaman beda status | PJM-… Dewi | "…berstatus "dipinjam", bukan "disetujui"." | N | ScanTest | | |
| SS-05 ★ | **QR tidak dikenal** (scan unit) | XYZ-999 | "✖ QR tidak dikenal: XYZ-999." + saran periksa label | N | ScanTest, PencocokanScanTest | | |
| SS-06 ★ | **Jenis tidak sesuai ajuan** | PHM-002 | "✖ Unit PHM-002 (pH Meter) tidak termasuk dalam ajuan ini." | N | ScanTest | | |
| SS-07 ★ | Unit sesuai | TBA-001 | "✔ … diterima.", Timbangan Analitik 1/1 ✔ | P | ScanTest | | |
| SS-08 ★ | **Jumlah scan melebihi ajuan** | TBA-002 setelah SS-07 | "✖ Jumlah Timbangan Analitik sudah cukup (1 unit)." | N | ScanTest, PencocokanScanTest | | |
| SS-09 | Unit sama diketik ulang | TBA-001 lagi (ketik manual) | "✖ Unit TBA-001 sudah dipilih." | N | ScanTest | | |
| SS-10 | Unit rusak / dipinjam | HPS-003 / CTP-001 (pada ajuan yang sesuai) | "…tidak bisa dipinjam (…)" | N | ScanTest | | |
| SS-11 | Scan kode peminjaman saat menunggu unit | PJM-… | "Yang di-scan bukan QR unit barang…" | N | ScanTest | | |
| SS-12 | Tombol selesai belum lengkap | Baru Timbangan Analitik 1/1 | Tombol nonaktif | N | ScanTest | | |
| SS-13 | Hapus dari daftar | Hapus TBA-001 | Progres 0/1; bisa di-scan ulang segera | P | — | | |
| SS-14 ★ | Selesaikan | TBA-001 + BNS-001 → Selesaikan | Sukses, kembali ke halaman scan, status dipinjam | P | ScanTest, PencocokanScanTest | | |
| SS-15 | Unit berubah status sebelum disimpan | Scan MKR-…, admin lain ubah unit jadi perbaikan, lalu simpan | Ditolak server, status tidak berubah | N | PencocokanScanTest | | |
| SS-16 | Transaksi dibatalkan saat scan | Peminjam membatalkan di tengah proses | Scan berikutnya ditolak "berstatus dibatalkan" | N | PencocokanScanTest | | |
| SS-17 | Ajuan multi-barang lengkap | Mikroskop Stereo×2 + pH Meter×1 dengan salah scan di tengah | Hanya unit valid masuk; selesai → dipinjam | P | PencocokanScanTest | | |

### 4.20 Scan — Pengembalian (SK)

| ID | Skenario | Input | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| SK-01 ★ | Buka dari scan unit | CTP-001 | Pinjaman Dewi terbuka, CTP-001 langsung masuk daftar | P | ScanTest | | |
| SK-02 | Unit tidak sedang dipinjam | MKR-001 | "Unit MKR-001 tidak sedang dipinjam." | N | ScanTest | | |
| SK-03 ★ | **QR tidak dikenal** | ZZZ-000 | "QR tidak dikenal: ZZZ-000." | N | ScanTest | | |
| SK-04 ★ | Unit milik pinjaman lain | Pinjaman Dewi terbuka, scan PHM-001 | "…bukan bagian dari peminjaman…" | N | ScanTest, PencocokanScanTest | | |
| SK-05 | Scan ulang | CTP-001 lagi | "Unit CTP-001 sudah di-scan." | N | ScanTest | | |
| SK-06 | Kondisi & catatan | CTP-001 rusak + catatan → Simpan | Unit perbaikan, catatan tersimpan | P | ScanTest | | |
| SK-07 ★ | Sebagian | Simpan CTP-001 saja | Tetap dipinjam; "Belum kembali" CTP-002 | P | ScanTest | | |
| SK-08 | Unit sudah kembali | CTP-001 lagi pada transaksi sama | "…sudah dikembalikan pada …" | N | ScanTest | | |
| SK-09 | Terlambat via scan | PHM-001 → Simpan | Peringatan terlambat; denda Rp2.000, selesai | P | — | | |
| SK-10 | Buka dengan QR kode peminjaman | QR status pinjaman Dewi | Pinjaman Dewi terbuka | P | ScanTest | | |
| SK-11 | Kode peminjaman sebagai unit | PJM-… saat transaksi terbuka | "bukan QR unit" | N | PencocokanScanTest | | |

### 4.21 Scanner USB 2D & Penanganan Error Scan (SU)
Prasyarat: scanner USB mode keyboard (akhiran Enter, sebagian tes juga dengan akhiran Tab); halaman Scan Barcode.

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| SU-01 ★ | Auto-focus saat halaman dibuka | Buka Scan Barcode | Kursor di kolom scanner; indikator hijau "Scanner siap" | P | — | | |
| SU-02 ★ | Proses otomatis saat Enter | Scan label MKR-… | Diproses tanpa klik apa pun | P | — | | |
| SU-03 | Scanner berakhiran Tab | Ubah setelan scanner ke suffix Tab | Tetap diproses, fokus tidak pindah | P | — | | |
| SU-04 ★ | Fokus hilang (klik area kosong / tab lain) | Klik area abu-abu, lalu scan | Indikator kuning lalu kembali hijau; hasil scan tetap diproses | P | — | | |
| SU-05 ★ | Fokus di kolom catatan | Ketik "retak" di catatan, lalu scan unit lain | Unit diproses; isi catatan tetap "retak" (tidak tercampur kode) | P | — | | |
| SU-06 | Ketikan manusia di catatan + Enter | Ketik pelan "retak" + Enter | Tidak dianggap scan | N | — | | |
| SU-07 ★ | Cegah scan ganda | Tekan pemicu scanner 2× cepat pada QR sama | Diproses sekali; info "diabaikan (scan ganda)" | N | — | | |
| SU-08 ★ | Dua kode berbeda beruntun cepat | Scan XYZ-999 lalu BNS-001 < 1 detik | Keduanya diproses berurutan (antrean), tidak ada yang hilang | P | — | | |
| SU-09 ★ | **Kode tidak dikenal** dari scanner | Scan QR lain (mis. URL website) | Pesan "QR tidak dikenal: …" + saran periksa label + bunyi gagal | N | PencocokanScanTest | | |
| SU-10 | Kode berkarakter sisa | Scanner menambah prefix "]Q1", spasi, CR/LF | Kode dibersihkan dan dikenali | B | PencocokanScanTest | | |
| SU-11 | Kode terlalu panjang | QR berisi > 100 karakter | "Kode terlalu panjang (… karakter)…" | B | PencocokanScanTest | | |
| SU-12 | Kode kosong | Enter saja / hanya "]Q1" | "Kode kosong. Scan QR atau ketik kodenya." | N | PencocokanScanTest | | |
| SU-13 | Sesi habis | Biarkan halaman > 2 jam / hapus cookie, lalu scan | "Sesi sudah habis. Muat ulang halaman (F5)…" | N | — | | |
| SU-14 | Server mati | Hentikan `php artisan serve`, lalu scan | "Koneksi ke server terputus…" | N | — | | |
| SU-15 | Huruf kecil (Caps Lock aktif) | Scan saat Caps Lock nyala | Tetap dikenali (tidak peka huruf besar/kecil) | B | PencocokanScanTest | | |
| SU-16 | Kamera di HTTP non-localhost | http://peminjaman-barang.test → Nyalakan kamera | Pesan "Kamera tidak tersedia… HTTPS/localhost…"; scanner USB tetap jalan | N | — | | |
| SU-17 | Izin kamera ditolak | Tolak izin | Pesan jelas; scanner/manual tetap jalan | N | — | | |
| SU-18 | Kamera & scanner bersamaan | Kamera menyala, scan dengan scanner USB | Keduanya diproses lewat antrean yang sama | P | — | | |
| SU-19 | Library lokal | Matikan internet → buka Scan Barcode → kamera | Kamera tetap bisa dinyalakan (tanpa CDN) | P | ScanTest | | |

### 4.22 Pengaturan — Data Admin (DA)
Prasyarat: login admin@peminjaman.test.

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| DA-01 ★ | Tambah admin | Pengaturan → Data Admin → Tambah; password 8+ | Tersimpan; admin baru bisa login | P | AkunAdminTest | | |
| DA-02 | Validasi | Nama kosong, email dipakai, HP "abc", password pendek/beda | Ditolak per kolom | N | AkunAdminTest | | |
| DA-03 | Ubah tanpa password | Ubah No. HP | Password lama tetap berlaku | P | AkunAdminTest | | |
| DA-04 | Reset password | Isi password baru | Login dengan password baru | P | AkunAdminTest | | |
| DA-05 ★ | Nonaktifkan diri sendiri | Edit akun sendiri → nonaktif | Ditolak | N | AkunAdminTest | | |
| DA-06 ★ | Hapus diri sendiri | (tombol tidak ada) / DELETE akun sendiri | Ditolak | N | AkunAdminTest | | |
| DA-07 | Hapus admin yang pernah memverifikasi | Hapus admin dengan riwayat verifikasi | Ditolak, saran nonaktifkan | N | AkunAdminTest | | |
| DA-08 | Hapus admin tanpa riwayat | Hapus petugas@ (seed baru) | Terhapus | P | AkunAdminTest | | |
| DA-09 | Minimal satu admin aktif | Nonaktifkan admin aktif terakhir selain diri sendiri (bila hanya tersisa satu) | Ditolak | B | — | | |
| DA-10 | URL Data Admin untuk peminjam | /admin/pengaturan/admin/{id peminjam}/edit | 404 | N | AkunAdminTest | | |

---
## 5. Matriks Ketertelusuran Kebutuhan

| Kode | Kebutuhan (CLAUDE.md) | Kasus uji | Feature Test |
|---|---|---|---|
| R01 | Admin login (route /admin dilindungi role); peminjam tanpa login di halaman publik | AU-01–AU-13, PJ-09, LP-08, DA-10 | RoleAccessTest, *(semua tes hak akses)* |
| R02 | CRUD kategori, lokasi, barang, unit (kode otomatis), peminjam, pengaturan | KT, LK, BR, UN, PJ, PG | KategoriLokasiTest, BarangUnitTest, PeminjamPengaturanTest |
| R03 | Ajuan publik: identitas + barang + jumlah, tujuan, tanggal; aturan lama/jumlah/terlambat; identitas dicocokkan via NIM | AJ-01–AJ-22, PG-05, PJ-07 | AjuanVerifikasiTest |
| R04 | Cek ketersediaan memperhitungkan tabrakan rentang (disetujui/dipinjam), dicek ulang saat disetujui | KS-01–KS-08, AJ-11, VR-03, VR-04 | KetersediaanTest, KetersediaanServiceTest, PersetujuanAjuanTest |
| R05 | Verifikasi: setujui / tolak dengan alasan wajib; notifikasi email | VR-01–VR-10, RN-04–RN-07 | AjuanVerifikasiTest, PersetujuanAjuanTest |
| R06 | Cek status (kode + NIM / tautan email) & pembatalan sebelum diambil | BT-01–BT-11, VR-10 | AjuanVerifikasiTest, PersetujuanAjuanTest |
| R07 | Serah-terima: cek jenis, status tersedia, jumlah; lengkap → dipinjam | ST-01–ST-08, SS-01–SS-17 | SerahPengembalianTest, ScanTest, PencocokanScanTest |
| R08 | Pengembalian: kondisi, tanggal kembali, boleh sebagian | PB-01–PB-10, SK-01–SK-11 | SerahPengembalianTest, ScanTest |
| R09 | Keterlambatan otomatis & denda per unit per hari (0 = tanpa denda) | PB-02–PB-03, PB-06, PB-11–PB-16, RN-01 | SerahPengembalianTest, PerhitunganDendaTest |
| R10 | Kedaluwarsa (scheduler) & pengingat | SC-01–SC-07 | SchedulerNotifikasiLaporanTest |
| R11 | Riwayat, dashboard, laporan PDF/Excel + filter tanggal | RN-02, RN-09, LP-01–LP-08 | SchedulerNotifikasiLaporanTest, PerhitunganDendaTest |
| R12 | QR per unit, cetak label massal, QR kode peminjaman untuk pengambilan | QR-01–QR-06, KQ-01–KQ-05 | QrLabelTest, ScanTest |
| R13 | Scan kamera (html5-qrcode lokal) & input manual | SS, SK, SU-16–SU-19 | ScanTest |
| R14 | Scanner USB: auto-focus, Enter, cegah scan ganda, pesan kode tidak dikenal | SU-01–SU-15 | PencocokanScanTest (sisi server) |
| R15 | Unit rusak/hilang tidak bisa dipinjam | UN-08–UN-09, KS-05, ST-05, SS-10, PB-05 | BarangUnitTest, KetersediaanServiceTest, ScanTest |
| R16 | Navigasi admin (urutan, subjudul deskripsi), responsif, menu aktif, pesan jelas | AU-14–AU-17, KT-06, SS-03 | RoleAccessTest, AkunAdminTest |
| R17 | Pengaturan: data admin, aturan peminjaman & ketentuan denda | DA-01–DA-10, PG-01–PG-05 | AkunAdminTest, PeminjamPengaturanTest |

---

## 6. Panduan Uji Manual Khusus

### 6.1 Scheduler
```bash
php artisan schedule:list
php artisan peminjaman:kedaluwarsa
php artisan peminjaman:kedaluwarsa --tanggal=YYYY-MM-DD   # simulasi "hari ini"
php artisan peminjaman:pengingat --tanggal=YYYY-MM-DD
php artisan schedule:work                                 # menjalankan jadwal saat pengembangan
```
- Kedaluwarsa: ajuan Budi (disetujui, tgl pinjam H) → `--tanggal` H+2 → kedaluwarsa.
- Pengingat: pinjaman Dewi (rencana kembali H+2) → `--tanggal` H+1 → 1 pengingat; ulangi → 0.
- Email: cari baris `Subject:` di `storage/logs/laravel.log`.
- Produksi (Windows): Task Scheduler menjalankan `php D:\Peminjaman_Barang\artisan schedule:run` setiap 1 menit.

### 6.2 Scanner USB 2D
1. Pastikan scanner dalam mode **USB HID keyboard**, akhiran **Enter** (CR). Uji di Notepad: scan label → teks `MKR-001` muncul lalu pindah baris.
2. Keyboard layout Windows dan scanner sama-sama **US** (bila tidak, tanda "-" bisa berubah).
3. Buka Scan Barcode; indikator harus hijau. Jalankan SU-01 s/d SU-15.
4. Bila scanner mengirim prefix simbologi (mis. `]Q1`), aplikasi otomatis membuangnya (SU-10).

### 6.3 Kamera dari HP
1. Laragon → Menu → Apache → SSL → Enabled; buka `https://peminjaman-barang.test` (terima sertifikat lokal).
2. Dari HP di jaringan yang sama, buka alamat IP komputer dengan `https://`.
3. Izinkan kamera; arahkan ke label (jarak ± 15–25 cm, cahaya cukup).

---

## 7. Tes Otomatis (Feature Test)

Jalankan: `php artisan test` (database terpisah `peminjaman_barang_test`). Hasil terakhir (refactor ULID + Enum): **189 tes lulus (1021 assertion)**.

| Kelas tes | Jumlah | Cakupan |
|---|---|---|
| Auth\* (Breeze) + ProfileTest | 15 | Login admin, reset/ubah password, profil |
| RoleAccessTest | 10 | Halaman publik tanpa login, panel admin terlindungi, peminjam tidak bisa login, registrasi dihapus, reset password khusus admin |
| Admin\KategoriLokasiTest | 6 | CRUD + larangan hapus + validasi |
| Admin\BarangUnitTest | 13 | Barang, foto, kode unit otomatis, kondisi/status unit |
| Admin\PeminjamPengaturanTest | 10 | Data peminjam (tanpa password), nonaktifkan, pengaturan |
| **Admin\AkunAdminTest** (baru) | 7 | Data Admin: tambah, validasi, reset password, larangan nonaktif/hapus diri sendiri, subjudul menu |
| Transaksi\KetersediaanTest | 9 | Rumus tabrakan rentang & status |
| Transaksi\AjuanVerifikasiTest | 22 | Ajuan publik + identitas (NIM/email), jebakan bot, setujui/tolak, cek status kode+NIM, tautan email, pembatalan |
| Transaksi\SerahPengembalianTest | 19 | Serah-terima, pengembalian, denda |
| Transaksi\SchedulerNotifikasiLaporanTest | 10 | Kedaluwarsa, pengingat, email (tautan status & admin), laporan, dashboard, halaman publik |
| Scan\QrLabelTest | 3 | QR SVG, label massal, hak akses |
| Scan\ScanTest | 16 | Buka transaksi & pencocokan scan |
| **KetersediaanServiceTest** (Sprint 5) | 6 | Penjumlahan tabrakan, multi-barang, unit rusak, terlambat memblokir, uji acak 200 skenario |
| **PersetujuanAjuanTest** (Sprint 5) | 9 | Atomik, overbooking, terlambat, persetujuan ganda, admin nonaktif, alasan spasi |
| **PencocokanScanTest** (Sprint 5) | 15 | Normalisasi kode scanner, panjang, alur multi-barang, race condition, kode PMJ lama ditolak |
| **PerhitunganDendaTest** (Sprint 5) | 13 | Kalender (lintas bulan/tahun/kabisat), unit hilang, perkiraan, tarif berubah, laporan |
| **KatalogFilterTest** (baru) | 4 | Katalog etalase: cari nama/kode, filter kategori & lokasi, cek tanggal + hanya tersedia, urutan & paginasi 9 alat |
| ExampleTest (Feature + Unit) | 2 | Halaman awal = katalog publik |

---

## 8. Log Cacat

| ID | Tanggal | Kasus uji | Deskripsi | Tingkat (Kritis/Tinggi/Sedang/Rendah) | Status | Perbaikan / commit |
|---|---|---|---|---|---|---|
| D-01 | Sprint 5 | KS-06 | Pinjaman terlambat hanya memblokir stok s/d hari ini, sehingga ajuan untuk hari-hari berikutnya bisa disetujui walau unit belum kembali | Tinggi | Selesai | Aturan diubah: terlambat memblokir semua tanggal ke depan (KetersediaanService) |
| D-02 | Sprint 5 | SU-08 | Label "memproses…" di indikator scanner kadang tetap tampil setelah proses selesai | Rendah | Selesai | Ganti `x-show` dengan `x-text` |
| D-03 | Pasca Sprint 5 | AU-11 | (Dicegah sejak desain) reset password terbuka untuk email peminjam sehingga peminjam bisa memperoleh akun login | Tinggi | Selesai | Reset password dibatasi role admin |
| | | | | | | |

---

## 9. Laporan Penutupan Pengujian

| Metrik | Nilai |
|---|---|
| Jumlah kasus uji manual | 235 (80 prioritas Tinggi ★) |
| Dieksekusi | ____ |
| Lulus / Gagal / Tertunda | ____ / ____ / ____ |
| Persentase lulus | ____ % |
| Kasus prioritas Tinggi (★) lulus | ____ / 80 |
| Feature Test otomatis | 189 lulus / 0 gagal |
| Cacat ditemukan (Kritis/Tinggi/Sedang/Rendah) | ____ |
| Cacat masih terbuka | ____ |

**Kesimpulan & rekomendasi:** ______________________________________________

**Keputusan rilis:** ☐ Layak dirilis  ☐ Layak dengan catatan  ☐ Belum layak

| Peran | Nama | Tanda tangan | Tanggal |
|---|---|---|---|
| Penguji 1 | | | |
| Penguji 2 | | | |
| Penguji 3 | | | |
| Perwakilan mitra | | | |

---

## 10. Perubahan: denda dihapus → penangguhan (19 Okt 2026)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| PN-01 ★ | Kembali terlambat → ditangguhkan | Kembali terlambat 12 Okt, penangguhan 7 hari, ajukan 15 Okt | Ditolak di kolom NIM: "…dapat dilakukan mulai 19 Okt 2026." | N | KeterlambatanPenangguhanTest | | |
| PN-02 | Penangguhan berakhir | Ajukan 19 Okt | Diterima | P | KeterlambatanPenangguhanTest | | |
| PN-03 | Penangguhan 0 hari | Aturan Peminjaman → 0 | Tidak ada penangguhan | B | KeterlambatanPenangguhanTest | | |
| PN-04 | Kembali tepat waktu | Kembali di hari rencana | Tidak ditangguhkan | P | KeterlambatanPenangguhanTest | | |
| PN-05 | Jumlah terlambat | Data Peminjam & panel verifikasi | "2×" / "Riwayat terlambat 2×" + info penangguhan | P | KeterlambatanPenangguhanTest | | |
| PN-06 ★ | Pengingat H-1, hari H, terlambat | `peminjaman:pengingat --tanggal=…` berturut-turut | 3 jenis email, terlambat 1×/hari, tanpa kata denda | P | KeterlambatanPenangguhanTest | | |
| PN-07 ★ | Laporan Statistik Pemakaian | Laporan → Statistik, rentang 1–10 Okt | Urut paling sering; alat 0× = "Tidak pernah dipinjam"; PDF/Excel | P | KeterlambatanPenangguhanTest | | |
| PN-08 | Denda hilang | Dashboard, detail transaksi, pengembalian, email, /admin/laporan/denda | Tidak ada nominal/kolom denda; laporan denda 404 | P | SerahPengembalianTest, KeterlambatanPenangguhanTest | | |

Catatan: kasus uji bertema denda di bagian sebelumnya (mis. PerhitunganDendaTest, LP denda) tidak berlaku lagi. Hasil terakhir: **186 tes lulus**.

## 11. Bukti PDF, pembatalan lewat email, persetujuan syarat (19 Okt 2026)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| BK-01 ★ | Cetak bukti dari detail transaksi | Admin → detail → Cetak Bukti (PDF) | PDF: kop lab, kode + QR, identitas, barang/unit, tanggal, tanda tangan | P | BuktiPembatalanSyaratTest | | |
| BK-02 | Bukti tanpa login | Buka /admin/peminjaman/{id}/bukti | Diarahkan ke login | N | BuktiPembatalanSyaratTest | | |
| BK-03 ★ | Lampiran email persetujuan | Admin setujui ajuan | Email berisi lampiran bukti-PJM-….pdf + tautan batal | P | BuktiPembatalanSyaratTest | | |
| BT-12 ★ | Batal dari tautan email | Klik tautan batal → Ya, batalkan | Status dibatalkan (menunggu & disetujui) | P | BuktiPembatalanSyaratTest | | |
| BT-13 | Tautan diubah / tanpa tanda tangan / kedaluwarsa | Ubah signature, buka setelah rencana kembali | 403, status tidak berubah | N | BuktiPembatalanSyaratTest | | |
| BT-14 | Ajuan sudah diambil | Buka tautan batal pinjaman dipinjam | Pesan "tidak bisa dibatalkan lagi", POST ditolak | N | BuktiPembatalanSyaratTest | | |
| SY-01 ★ | Checkbox syarat wajib | Kirim ajuan tanpa centang | Ditolak "Centang persetujuan syarat…" | N | BuktiPembatalanSyaratTest | | |
| SY-02 | Admin ubah teks syarat | Aturan Peminjaman → syarat (>255 karakter) | Tersimpan & tampil di form ajuan; kosong ditolak | P/N | BuktiPembatalanSyaratTest, PeminjamPengaturanTest | | |

Hasil terakhir: **194 tes lulus**.

## 12. Template & upload surat peminjaman (19 Okt 2026)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| SR-01 ★ | Info & template | Buka Ajukan Peminjaman → Buka Template Surat | Kotak info + estimasi tampil; file DOCX terunduh | P | SuratPeminjamanTest | | |
| SR-02 ★ | Surat wajib | Kirim tanpa file | "Unggah surat peminjaman (PDF, DOC, atau DOCX)." | N | SuratPeminjamanTest | | |
| SR-03 | Format / ukuran salah | JPG; PDF 5 MB | Ditolak dengan pesan format / maks 4 MB | N/B | SuratPeminjamanTest | | |
| SR-04 ★ | Surat privat | Kirim ajuan valid, buka /admin/peminjaman/{id}/surat tanpa login & sebagai admin | Tamu → login; admin bisa membuka; file tidak ada di folder publik | P/N | SuratPeminjamanTest | | |
| SR-05 | Admin ganti template & estimasi | Aturan Peminjaman → unggah PDF, estimasi 5 | Template baru terunduh di form ajuan; .exe ditolak | P/N | SuratPeminjamanTest | | |

Hasil terakhir: **200 tes lulus**.

## 13. Audit keamanan & bug (20 Okt 2026)

| ID | Temuan | Tingkat | Perbaikan | Tes |
|---|---|---|---|---|
| AU-01 | Ekstensi file template surat diambil dari nama kiriman klien; berpotensi menyimpan file berbahaya di folder publik | Tinggi (sudah tertahan validasi `mimes` Laravel yang menolak nama .php) | Ekstensi ditentukan dari isi file (`extension()`) + whitelist pdf/doc/docx | AuditKeamananTest |
| AU-02 | Ekspor Excel: teks dari form publik yang diawali `=`, `+`, `-`, `@` bisa dijalankan sebagai formula (formula injection) | Tinggi | `LaporanExport` memakai value binder: teks seperti itu disimpan sebagai TEKS | AuditKeamananTest |
| AU-03 | Header tabel Excel bergeser ke baris 4 (baris kosong dibuang library), gaya tebal jatuh ke baris data pertama | Sedang | Baris pemisah diisi `''` | AuditKeamananTest |
| AU-04 | Panel verifikasi admin menampilkan pesan penangguhan dengan kata "Anda" | Rendah | `pesanPenangguhan(..., untukAdmin: true)` → "Peminjam …" | KeterlambatanPenangguhanTest |
| AU-05 | Uji asap seluruh halaman GET (publik & admin) + detail semua status + PDF/Excel semua laporan | — | Tidak ada error 5xx | SemuaHalamanTest |

Diperiksa dan aman: semua route /admin di bawah auth + role:admin; form publik di-throttle + honeypot; tidak ada SQL mentah dengan input pengguna; keluaran Blade ter-escape (yang tidak di-escape hanya SVG QR buatan sistem); persetujuan mengunci baris barang (anti persetujuan ganda); serah-terima & pengembalian memvalidasi unit milik transaksi; surat peminjaman di disk privat; tautan batal bertanda tangan + kedaluwarsa.

Hasil terakhir: **203 tes lulus**.

## 14. Jadwal Pemakaian Alat (21 Okt 2026)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| JD-01 ★ | Timeline per unit | Admin → Transaksi → Jadwal Pemakaian, mulai 14 Okt | Balok dipinjam/terlambat/disetujui/menunggu di unit & tanggal yang benar; klik balok → detail | P | JadwalPemakaianTest | | |
| JD-02 ★ | Versi publik | Buka /jadwal | Tanpa nama, kode PJM, dan ajuan menunggu | P | JadwalPemakaianTest | | |
| JD-03 | Ajukan dari jadwal | Klik sel kosong 20 Okt | Form ajuan terisi alat & tanggal 20–21 Okt | P | JadwalPemakaianTest | | |
| JD-04 | Unit perbaikan | Unit rusak | Baris abu-abu "Tidak tersedia", tidak menerima pesanan | N | JadwalPemakaianTest | | |
| JD-05 | Per Alat & Ringkasan | Tab Per Alat / Ringkasan Mingguan | Terpakai/kapasitas per hari; metrik minggu | P | JadwalPemakaianTest | | |
| JD-06 | Filter & validasi | Cari "Mikroskop"; tab=salah | Hanya unit cocok; tab salah ditolak | P/N | JadwalPemakaianTest | | |

Hasil terakhir: **208 tes lulus**.

## 15. Menu dirampingkan (21 Okt 2026)

| ID | Skenario | Langkah | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| MR-01 | Menu Pengembalian dihapus | Buka sidebar; buka /admin/pengembalian | Tidak ada menu Pengembalian; URL 404; tombol "Catat Pengembalian" di Peminjaman Aktif | P/N | SchedulerNotifikasiLaporanTest | | |
| MR-02 ★ | Unduh dari Riwayat | Riwayat → filter status Selesai → Unduh PDF / Excel | File berisi hanya transaksi sesuai filter, periode tercantum di kop | P | RiwayatUnduhTest | | |
| MR-03 | Laporan Transaksi dihapus | Buka /admin/laporan/peminjaman | 404; menu Laporan tinggal 4 | N | SchedulerNotifikasiLaporanTest | | |

Hasil terakhir: **209 tes lulus**.

