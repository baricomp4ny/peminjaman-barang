# Dokumen STLC — Sistem Informasi Peminjaman Barang Berbasis Web dengan QR Code

| Item | Keterangan |
|---|---|
| Aplikasi | Sistem Informasi Peminjaman Barang Berbasis Web dengan QR Code |
| Mitra | (isi sesuai `MITRA_NAMA` di `.env`) |
| Versi yang diuji | Commit Sprint 5 (`git log --oneline -1`) |
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
| 2 — Web dasar | CRUD kategori, lokasi, jenis barang (+ foto, kode prefix), unit barang (kode otomatis `PRJ-001`, tambah 1–50 unit sekaligus), data peminjam (tambah, ubah, reset password, nonaktifkan), pengaturan aturan bisnis; larangan hapus data yang masih dipakai; pesan validasi bahasa Indonesia |
| 3 — Transaksi & laporan | Ajuan peminjaman (multi-barang, cek sisa unit langsung), verifikasi admin (setujui/tolak dengan alasan wajib), pembatalan, serah-terima, pengembalian (boleh sebagian, kondisi baik/rusak/hilang), keterlambatan & denda, riwayat, dashboard admin & beranda peminjam, laporan peminjaman/pengembalian/inventaris/keterlambatan/denda (web, PDF, Excel), notifikasi aplikasi + email, scheduler kedaluwarsa & pengingat H-1 |
| 4 — QR Code & scan | QR SVG per unit, cetak label massal A4, kartu QR peminjam bertanda tangan (HMAC), halaman Scan QR (kamera html5-qrcode lokal + input manual) untuk serah-terima & pengembalian, pencocokan hasil scan dengan ajuan |
| 5 — Hardware & pengujian | Dukungan scanner USB 2D (penangkap ketikan cepat global, auto-focus + indikator "Scanner siap", proses saat Enter/Tab, antrean, cegah scan ganda, pembersihan kode, pesan error jaringan/sesi), aturan pinjaman terlambat memblokir stok sampai unit kembali, Feature Test terfokus, dokumen STLC |

### 1.2 Per peran

**Admin** (sidebar)
- Dashboard: ajuan menunggu, siap diambil, sedang dipinjam, terlambat, denda bulan ini, status unit, daftar jatuh tempo.
- Scan QR Code: mode serah-terima & pengembalian (kamera, scanner USB, input manual).
- Transaksi: Ajuan Peminjaman (badge jumlah menunggu), Peminjaman (tab siap diambil/dipinjam/terlambat), Pengembalian, Riwayat.
- Data Barang: Jenis Barang, Unit Barang, Kategori, Lokasi, Cetak Label QR.
- Data Peminjam, Laporan (5 jenis, PDF/Excel, filter tanggal), Pengaturan, menu avatar (profil, ganti password, keluar).

**Peminjam** (navbar)
- Beranda (pinjaman berjalan, peringatan terlambat, notifikasi terbaru), Katalog Barang (sisa unit hari ini), Ajukan Peminjaman, Pinjaman Saya (detail, batalkan), Notifikasi (badge belum dibaca), Profil, Kartu QR.

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

Semua akun memakai password **`password`**. Tanggal relatif terhadap hari seeder dijalankan (H = hari ini).

| Akun | Peran | Data penting |
|---|---|---|
| admin@peminjaman.test | Admin | — |
| andi@peminjaman.test | Peminjam (2201001) | Ajuan **menunggu** Proyektor×1 + Layar×1 (H+2 s/d H+4); riwayat **selesai** (PRJ-004 terlambat 1 hari Rp1.000, KBL-003 kembali rusak) |
| budi@peminjaman.test | Peminjam (2201002) | Ajuan **disetujui** Kamera×1 + Tripod×1 (H s/d H+2); ajuan **dibatalkan** |
| citra@peminjaman.test | Peminjam (2201003) | Ajuan **ditolak** (dengan alasan); ajuan **kedaluwarsa** |
| dewi@peminjaman.test | Peminjam (2201004) | **Dipinjam** MIC-001, MIC-002 (rencana kembali H+2) |
| eko@peminjaman.test | Peminjam (2201005) | **Dipinjam & terlambat** LPT-001 (rencana kembali H−2) |

Unit khusus: `SPK-003` rusak/perbaikan, `SLD-002` hilang, `KBL-003` rusak/perbaikan. Jenis barang & kode: Proyektor PRJ(4), Laptop LPT(4), Speaker SPK(3), Kamera DSLR KMR(3), Tripod TRP(3), Multimeter MLT(3), Solder SLD(2), Mikrofon MIC(3), Layar Proyektor LYR(2), Kabel Roll KBL(3).

---

## 4. Daftar Kasus Uji

Kolom: **J** = jenis (P positif, N negatif/input salah, B nilai batas); **Tes otomatis** = kelas Feature Test yang mencakup logika yang sama (`—` = hanya manual). Prioritas: kasus bertanda ★ adalah prioritas Tinggi.

### 4.1 Autentikasi & Hak Akses (AU)
Prasyarat: seed baru. Penguji: ________ Tanggal: ________

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| AU-01 ★ | Login admin | admin@peminjaman.test / password | Masuk ke /admin/dashboard dengan sidebar | P | AuthenticationTest, RoleAccessTest | | |
| AU-02 ★ | Login peminjam | andi@peminjaman.test / password | Masuk ke /peminjam/beranda dengan navbar | P | RoleAccessTest | | |
| AU-03 | Password salah | andi@… / salah123 | "Email atau password salah.", tetap di login | N | AuthenticationTest | | |
| AU-04 | Login berulang gagal | 6× password salah | Pesan dibatasi "Terlalu banyak percobaan…" | N | — | | |
| AU-05 ★ | Akun nonaktif login | Nonaktifkan Budi, login Budi | "Akun Anda dinonaktifkan. Hubungi admin." | N | RoleAccessTest | | |
| AU-06 ★ | Akun dinonaktifkan saat sedang login | Budi login; admin menonaktifkan; Budi klik menu | Budi dikeluarkan ke halaman login | N | RoleAccessTest | | |
| AU-07 | Registrasi peminjam | Isi nama, NIM baru, email baru, password 8+ | Akun dibuat sebagai peminjam aktif, masuk beranda | P | RegistrationTest | | |
| AU-08 | Registrasi tanpa NIM / NIM dipakai | NIM kosong / 2201001 | Ditolak dengan pesan | N | RegistrationTest | | |
| AU-09 ★ | Sisipkan role admin saat registrasi | Tambah field `role=admin` via inspect element | Akun tetap peminjam | N | RegistrationTest | | |
| AU-10 ★ | Peminjam buka halaman admin | Login Andi → /admin/dashboard, /admin/kategori, /admin/laporan/denda | 403 | N | RoleAccessTest, KategoriLokasiTest | | |
| AU-11 ★ | Admin buka halaman peminjam | Login admin → /peminjam/beranda | 403 | N | RoleAccessTest | | |
| AU-12 | Tamu buka halaman terlindungi | Logout → /admin/barang | Diarahkan ke login | N | RoleAccessTest | | |
| AU-13 | Ubah profil | Profil → ubah nama | Tersimpan | P | ProfileTest | | |
| AU-14 | Ganti password | Avatar → Ganti Password; password lama benar, baru 8+ | Tersimpan; login dengan password baru berhasil | P | PasswordUpdateTest | | |
| AU-15 | Ganti password, password lama salah | Password lama salah | Ditolak | N | PasswordUpdateTest | | |
| AU-16 | Hapus akun sendiri | DELETE /profile | Tidak tersedia (405) | N | ProfileTest | | |
| AU-17 | Lupa password | Forgot password → email terdaftar | Tautan reset tercatat di log email | P | PasswordResetTest | | |
| AU-18 | Logout | Avatar → Keluar | Kembali ke login | P | AuthenticationTest | | |
| AU-19 | Menu aktif & responsif | Buka beberapa menu; perkecil jendela < 640 px | Menu aktif ditandai; sidebar jadi tombol ☰, navbar jadi menu mobile | P | — | | |

### 4.2 Kategori (KT)
Prasyarat: login admin. Penguji: ________ Tanggal: ________

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| KT-01 | Tambah kategori | "Alat Olahraga" | Tersimpan, pesan sukses | P | KategoriLokasiTest | | |
| KT-02 | Nama kosong | "" | "Nama kategori wajib diisi." | N | KategoriLokasiTest | | |
| KT-03 | Nama duplikat | "Elektronik" | "Nama kategori sudah digunakan." | N | KategoriLokasiTest | | |
| KT-04 | Nama terlalu panjang | 101 karakter | Ditolak (maks 100) | B | KategoriLokasiTest | | |
| KT-05 | Simpan ulang tanpa ubah nama | Edit "Fotografi" → Simpan | Tersimpan, tidak dianggap duplikat | P | KategoriLokasiTest | | |
| KT-06 ★ | Hapus kategori yang dipakai | Hapus "Elektronik" | Ditolak, pesan merah | N | KategoriLokasiTest | | |
| KT-07 | Hapus kategori kosong | Hapus KT-01 | Terhapus | P | KategoriLokasiTest | | |
| KT-08 | Cari | "foto" | Hanya Fotografi | P | — | | |

### 4.3 Lokasi (LK)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| LK-01 | Tambah lokasi | "Ruang Rapat", "Lt. 4" | Tersimpan | P | KategoriLokasiTest | | |
| LK-02 | Nama kosong / duplikat | "" / "Gudang Utama" | Ditolak | N | KategoriLokasiTest | | |
| LK-03 | Keterangan > 255 | 256 karakter | Ditolak | B | — | | |
| LK-04 ★ | Hapus lokasi berisi unit | Hapus "Gudang Utama" | Ditolak | N | KategoriLokasiTest | | |
| LK-05 | Jumlah unit bisa diklik | Klik angka unit | Daftar unit terfilter lokasi | P | — | | |

### 4.4 Jenis Barang (BR)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| BR-01 | Tambah barang + foto | "Printer", kode "prt", foto .jpg 500 KB | Tersimpan, kode "PRT", foto tampil | P | BarangUnitTest | | |
| BR-02 | Field wajib kosong | nama/kode/kategori kosong | Ketiganya ditolak | N | BarangUnitTest | | |
| BR-03 | Kode duplikat | "PRJ" | Ditolak | N | BarangUnitTest | | |
| BR-04 | Format kode salah | "P1", "A", "ABCDEF" | Ditolak: 2–5 huruf | B | BarangUnitTest | | |
| BR-05 | Foto bukan gambar | .pdf | Ditolak | N | BarangUnitTest | | |
| BR-06 | Foto > 2 MB | .jpg 3 MB | Ditolak | B | BarangUnitTest | | |
| BR-07 ★ | Ubah kode barang yang sudah punya unit | PRJ → PRY | Ditolak, kode terkunci | N | BarangUnitTest | | |
| BR-08 | Hapus foto | Centang "Hapus foto" | Foto hilang | P | — | | |
| BR-09 ★ | Hapus barang yang punya unit | Hapus Proyektor | Ditolak | N | BarangUnitTest | | |
| BR-10 | Hapus barang tanpa unit | Hapus Printer | Terhapus | P | — | | |
| BR-11 | Filter + cari | Kategori Fotografi, "tri" | Hanya Tripod | P | — | | |

### 4.5 Unit Barang (UN)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| UN-01 ★ | Tambah 1 unit | Proyektor, jumlah 1 | `PRJ-005`, tersedia | P | BarangUnitTest | | |
| UN-02 ★ | Tambah banyak unit | Proyektor, jumlah 3 | `PRJ-006`–`PRJ-008`, pesan menyebut rentang | P | BarangUnitTest | | |
| UN-03 | Pratinjau kode | Pilih jenis barang | Kode berikutnya tampil | P | BarangUnitTest | | |
| UN-04 | Nomor setelah unit dihapus | Hapus unit terakhir tanpa riwayat, tambah 1 | Lanjut dari nomor terbesar yang ada | B | BarangUnitTest | | |
| UN-05 | Nomor > 999 | (data uji) PRJ-999 lalu tambah 2 | PRJ-1000, PRJ-1001 | B | BarangUnitTest | | |
| UN-06 | Jumlah di luar batas | 0, 51, -1, "abc" | Ditolak 1–50 | B | BarangUnitTest | | |
| UN-07 | Tanggal perolehan masa depan | Besok | Ditolak | N | BarangUnitTest | | |
| UN-08 | Unit baru kondisi rusak | Kondisi rusak | Status otomatis perbaikan | P | BarangUnitTest | | |
| UN-09 ★ | Kombinasi kondisi/status salah | Rusak + tersedia; hilang + perbaikan | Ditolak | N | BarangUnitTest | | |
| UN-10 ★ | Ubah unit sedang dipinjam | Edit MIC-001 | Form terkunci; paksa kirim ditolak | N | BarangUnitTest | | |
| UN-11 ★ | Hapus unit yang punya riwayat | Hapus PRJ-004 | Ditolak | N | BarangUnitTest | | |
| UN-12 | Hapus unit tanpa riwayat | Hapus unit UN-01 | Terhapus | P | BarangUnitTest | | |
| UN-13 | Filter status | Status perbaikan | SPK-003, KBL-003 | P | — | | |

### 4.6 Data Peminjam (PJ)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| PJ-01 | Tambah peminjam | Data lengkap, password 8+ | Tersimpan sebagai peminjam | P | PeminjamPengaturanTest | | |
| PJ-02 | Email / NIM duplikat | andi@… / 2201001 | Ditolak | N | PeminjamPengaturanTest | | |
| PJ-03 | Email format salah | "andi@" | Ditolak | N | PeminjamPengaturanTest | | |
| PJ-04 | No. HP berisi huruf | "08abc" | Ditolak | N | PeminjamPengaturanTest | | |
| PJ-05 | Password pendek / konfirmasi beda | "123" / beda | Ditolak | N | PeminjamPengaturanTest | | |
| PJ-06 | Edit tanpa password | Ubah No. HP | Password lama tetap berlaku | P | PeminjamPengaturanTest | | |
| PJ-07 | Reset password | Isi password baru | Login dengan password baru | P | PeminjamPengaturanTest | | |
| PJ-08 ★ | Hapus peminjam dengan riwayat | Hapus Andi | Ditolak, saran nonaktifkan | N | PeminjamPengaturanTest | | |
| PJ-09 ★ | Ubah akun admin lewat URL peminjam | /admin/peminjam/1/edit | 404 | N | PeminjamPengaturanTest | | |
| PJ-10 | Detail peminjam | Klik Andi | Profil + 10 transaksi terakhir | P | PeminjamPengaturanTest | | |

### 4.7 Pengaturan (PG)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| PG-01 | Ubah nilai | 14 / 5 / 0 / 2 | Tersimpan | P | PeminjamPengaturanTest | | |
| PG-02 | Lama pinjam di luar batas | 0, 91 | Ditolak (1–90) | B | PeminjamPengaturanTest | | |
| PG-03 | Denda negatif | -500 | Ditolak | B | PeminjamPengaturanTest | | |
| PG-04 | Bukan angka / kosong | "tiga", "" | Ditolak, nilai lama tetap | N | PeminjamPengaturanTest | | |
| PG-05 ★ | Pengaturan berlaku di ajuan | Lama maks 2 → ajukan 3 hari | Ditolak "maksimal 2 hari" | P | AjuanVerifikasiTest | | |

### 4.8 Ajuan Peminjaman (AJ)
Prasyarat: login peminjam. Penguji: ________ Tanggal: ________

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| AJ-01 ★ | Ajuan valid | Laptop×1, besok s/d lusa, tujuan diisi | Status menunggu, kode PJM-YYYYMMDD-NNNN, admin dapat email | P | AjuanVerifikasiTest | | |
| AJ-02 | Kode berurutan per hari | Dua ajuan hari ini | Nomor urut naik | P | AjuanVerifikasiTest | | |
| AJ-03 | Tanggal pinjam kemarin | (inspect element) | Ditolak | N | AjuanVerifikasiTest | | |
| AJ-04 | Kembali < pinjam | tgl 20 → tgl 19 | Ditolak | N | AjuanVerifikasiTest | | |
| AJ-05 ★ | Lama > maks | tgl 1 → tgl 9 | Ditolak "maksimal 7 hari" | B | AjuanVerifikasiTest | | |
| AJ-06 ★ | Lama = maks | tgl 1 → tgl 8 | Diterima | B | AjuanVerifikasiTest | | |
| AJ-07 | Pinjam & kembali hari sama | H → H | Diterima | B | AjuanVerifikasiTest | | |
| AJ-08 ★ | Total unit > maks | Laptop 2 + Tripod 2 | Ditolak "maksimal 3 unit" | B | AjuanVerifikasiTest | | |
| AJ-09 | Barang sama dua baris | Proyektor 1 + 1 | Ditolak "gabungkan jumlahnya" | N | AjuanVerifikasiTest | | |
| AJ-10 | Jumlah 0 / tanpa barang / tujuan kosong | — | Ditolak | N | AjuanVerifikasiTest | | |
| AJ-11 ★ | Stok kurang pada rentang | Kamera×3, H s/d H+1 | Peringatan merah "Sisa 2"; server menolak | N | AjuanVerifikasiTest | | |
| AJ-12 | Rentang tidak bertabrakan | Kamera×3 setelah H+2 | Diterima | P | AjuanVerifikasiTest | | |
| AJ-13 | Ajuan menunggu tidak mengikat stok | Dua akun ajukan Kamera×3 tanggal sama | Keduanya diterima sebagai menunggu | P | KetersediaanTest | | |
| AJ-14 ★ | Peminjam terlambat mengajukan | Login Eko | Peringatan, tombol nonaktif, server menolak | N | AjuanVerifikasiTest | | |
| AJ-15 | Katalog | Katalog → cari, filter kategori | Sisa unit hari ini tampil; tombol Pinjam mengisi barang | P | SchedulerNotifikasiLaporanTest | | |

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
| VR-03 ★ | Cegah overbooking | Dua ajuan Kamera×2 bertabrakan; setujui keduanya | Pertama sukses, kedua "Stok tidak cukup…" | N | AjuanVerifikasiTest, PersetujuanAjuanTest | | |
| VR-04 ★ | Persetujuan atomik | Ajuan Proyektor×1 + Solder×2 (sisa 1) | Gagal; status tetap menunggu; tidak ada notifikasi | N | PersetujuanAjuanTest | | |
| VR-05 | Setujui dua kali | Klik Setujui lagi (tab lain) | "status ajuan sekarang disetujui" | N | PersetujuanAjuanTest | | |
| VR-06 ★ | Tolak tanpa alasan / alasan pendek / spasi saja | "", "pendek", "     " | Ditolak, form tolak tetap terbuka | N | AjuanVerifikasiTest, PersetujuanAjuanTest | | |
| VR-07 ★ | Tolak dengan alasan | "Barang dipakai untuk ujian praktik." | Status ditolak, alasan tampil di peminjam, notifikasi | P | AjuanVerifikasiTest | | |
| VR-08 | Verifikasi ajuan yang dibatalkan | POST setujui | Ditolak | N | AjuanVerifikasiTest | | |
| VR-09 | Badge ajuan | Setelah VR-02 | Badge berkurang 1 | P | — | | |
| VR-10 | Pembatalan mengembalikan stok | Setujui A (habiskan stok), A dibatalkan, setujui B | B berhasil | P | PersetujuanAjuanTest | | |

### 4.11 Pembatalan (BT)
Prasyarat: login peminjam.

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| BT-01 | Batalkan ajuan menunggu | Andi → Batalkan | Status dibatalkan | P | AjuanVerifikasiTest | | |
| BT-02 | Batalkan ajuan disetujui | Budi → Batalkan | Status dibatalkan | P | AjuanVerifikasiTest | | |
| BT-03 ★ | Batalkan yang sudah dipinjam | Dewi → POST batal | Ditolak | N | AjuanVerifikasiTest | | |
| BT-04 ★ | Buka/batalkan milik orang lain | Ganti ID di URL | 403 | N | AjuanVerifikasiTest | | |

### 4.12 Serah-terima lewat form (ST)
Prasyarat: login admin; ajuan Budi disetujui.

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| ST-01 ★ | Serah-terima lengkap | Centang KMR-001, TRP-001 | Status dipinjam, unit dipinjam | P | SerahPengembalianTest | | |
| ST-02 | Ketik kode manual | "kmr-001, trp-001" | Diterima | P | SerahPengembalianTest | | |
| ST-03 ★ | Jenis salah | LPT-002 | "tidak termasuk dalam ajuan" | N | SerahPengembalianTest | | |
| ST-04 ★ | Jumlah kurang / lebih | KMR-001 saja / KMR-001+002+TRP-001 | "belum lengkap" / "sudah cukup" | N | SerahPengembalianTest | | |
| ST-05 | Unit rusak / dipinjam orang lain | SPK-003 / MIC-001 | "tidak bisa dipinjam" | N | SerahPengembalianTest | | |
| ST-06 | Kode tidak dikenal | XYZ-999 | "Kode unit tidak dikenal" | N | SerahPengembalianTest | | |
| ST-07 | Sebelum tanggal pinjam | Ajuan Andi (H+2) setelah disetujui | Peringatan; tombol nonaktif | N | SerahPengembalianTest | | |
| ST-08 | Lewat batas ambil | (data uji) ajuan disetujui tgl pinjam H−2 | "Batas waktu pengambilan sudah lewat" | N | SerahPengembalianTest | | |

### 4.13 Pengembalian & Denda (PB)
Prasyarat: login admin.

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| PB-01 ★ | Tepat waktu | Dewi, semua baik | Denda Rp0, status selesai, unit tersedia | P | SerahPengembalianTest | | |
| PB-02 ★ | Terlambat | Eko (LPT-001, terlambat 2 hari) | Denda Rp2.000 | P | SerahPengembalianTest | | |
| PB-03 ★ | Denda per unit | 2 unit terlambat 3 hari | Rp6.000 | P | SerahPengembalianTest | | |
| PB-04 ★ | Pengembalian sebagian | Dewi: MIC-001 saja | Tetap dipinjam; berikutnya hanya MIC-002 | P | SerahPengembalianTest | | |
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

### 4.14 Riwayat, Notifikasi, Dashboard (RN)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| RN-01 | Tanda terlambat | Peminjaman → tab Terlambat | Eko dengan badge "+2 hari" | P | SchedulerNotifikasiLaporanTest | | |
| RN-02 | Filter riwayat | Status selesai; rentang tanggal; cari NIM | Hanya data cocok | P | SchedulerNotifikasiLaporanTest | | |
| RN-03 | Rentang riwayat terbalik | Dari > sampai | Pesan validasi | N | — | | |
| RN-04 ★ | Badge notifikasi | Admin setujui ajuan Andi | Badge merah di menu Notifikasi Andi | P | — | | |
| RN-05 | Buka notifikasi | Klik notifikasi | Ke detail pinjaman, ditandai dibaca | P | SchedulerNotifikasiLaporanTest | | |
| RN-06 | Tandai semua dibaca | Klik tombol | Badge hilang | P | SchedulerNotifikasiLaporanTest | | |
| RN-07 ★ | Notifikasi milik orang lain | /peminjam/notifikasi/{id} user lain | 404 | N | SchedulerNotifikasiLaporanTest | | |
| RN-08 | Email notifikasi | Ajukan, setujui, tolak, kembalikan | Subjek tercatat di storage/logs/laravel.log | P | AjuanVerifikasiTest | | |
| RN-09 | Dashboard admin | Buka dashboard | Angka sesuai; kartu bisa diklik | P | SchedulerNotifikasiLaporanTest | | |
| RN-10 | Beranda peminjam | Login Eko | Peringatan terlambat, pinjaman berjalan, notifikasi | P | SchedulerNotifikasiLaporanTest | | |

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
| QR-01 | Unduh QR unit | Unit Barang → "QR" PRJ-001 | qr-PRJ-001.svg; di-scan HP berisi "PRJ-001" | P | QrLabelTest | | |
| QR-02 | Label per jenis | Detail Proyektor → Cetak Label QR | Label PRJ-001…004 (QR, kode, nama, mitra) | P | QrLabelTest | | |
| QR-03 | Label sesuai filter | Filter lokasi → Cetak Label (sesuai filter) | Hanya unit lokasi itu | P | QrLabelTest | | |
| QR-04 | Label terpilih | Centang 3 unit → Cetak label terpilih | Tepat 3 label; tombol nonaktif bila 0 | P | QrLabelTest | | |
| QR-05 | Ukuran label | 3 / 4 / 5 per baris | Grid berubah; `?kolom=9` ditolak | B | QrLabelTest | | |
| QR-06 ★ | Hasil cetak | Ctrl+P | Hanya label, tidak terpotong; QR cetakan terbaca scanner | P | — | | |

### 4.18 Kartu QR Peminjam (KQ)

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| KQ-01 | Tampilan kartu | Budi → avatar → Kartu QR | Kartu ukuran KTP: nama, NIM, prodi, QR | P | QrLabelTest | | |
| KQ-02 ★ | Kartu membuka ajuan | Admin scan kartu Budi (mode serah) | Ajuan Budi terbuka | P | ScanTest | | |
| KQ-03 ★ | Kartu palsu | `PMJ.3.0000000000` | "Kartu QR tidak valid atau sudah tidak berlaku." | N | ScanTest, QrLabelTest | | |
| KQ-04 | Kartu milik admin | (data uji) isi kartu admin | Tidak berlaku | N | PencocokanScanTest | | |
| KQ-05 | Peminjam tanpa ajuan disetujui | Kartu Citra | "…tidak memiliki ajuan disetujui…" | N | ScanTest | | |
| KQ-06 | Peminjam nonaktif | Nonaktifkan Budi, scan kartunya | "Akun … dinonaktifkan." | N | — | | |
| KQ-07 | Dua ajuan disetujui | Kartu peminjam dengan 2 ajuan | Muncul daftar pilihan | P | ScanTest | | |

### 4.19 Scan — Serah-terima (SS)
Prasyarat: admin → Scan QR → mode Serah-terima; ajuan Budi (Kamera×1, Tripod×1). Input lewat kamera, scanner USB, atau ketik + Enter.

| ID | Skenario | Input | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| SS-01 ★ | Buka dengan kode peminjaman | PJM-… Budi | Transaksi terbuka, Kamera 0/1, Tripod 0/1 | P | ScanTest | | |
| SS-02 | Kode unit sebelum transaksi | KMR-001 | "…Scan kartu peminjam atau kode peminjaman terlebih dahulu." | N | ScanTest | | |
| SS-03 ★ | **QR tidak dikenal** (buka transaksi) | ABC-123 | "QR tidak dikenal: ABC-123." + bunyi gagal + kotak merah berkedip | N | ScanTest | | |
| SS-04 | Kode peminjaman beda status | PJM-… Dewi | "…berstatus "dipinjam", bukan "disetujui"." | N | ScanTest | | |
| SS-05 ★ | **QR tidak dikenal** (scan unit) | XYZ-999 | "✖ QR tidak dikenal: XYZ-999." + saran periksa label | N | ScanTest, PencocokanScanTest | | |
| SS-06 ★ | **Jenis tidak sesuai ajuan** | LPT-002 | "✖ Unit LPT-002 (Laptop) tidak termasuk dalam ajuan ini." | N | ScanTest | | |
| SS-07 ★ | Unit sesuai | KMR-001 | "✔ … diterima.", Kamera 1/1 ✔ | P | ScanTest | | |
| SS-08 ★ | **Jumlah scan melebihi ajuan** | KMR-002 setelah SS-07 | "✖ Jumlah Kamera DSLR sudah cukup (1 unit)." | N | ScanTest, PencocokanScanTest | | |
| SS-09 | Unit sama diketik ulang | KMR-001 lagi (ketik manual) | "✖ Unit KMR-001 sudah dipilih." | N | ScanTest | | |
| SS-10 | Unit rusak / dipinjam | SPK-003 / MIC-001 (pada ajuan yang sesuai) | "…tidak bisa dipinjam (…)" | N | ScanTest | | |
| SS-11 | Scan kode peminjaman saat menunggu unit | PJM-… | "Yang di-scan bukan QR unit barang…" | N | ScanTest | | |
| SS-12 | Tombol selesai belum lengkap | Baru Kamera 1/1 | Tombol nonaktif | N | ScanTest | | |
| SS-13 | Hapus dari daftar | Hapus KMR-001 | Progres 0/1; bisa di-scan ulang segera | P | — | | |
| SS-14 ★ | Selesaikan | KMR-001 + TRP-001 → Selesaikan | Sukses, kembali ke halaman scan, status dipinjam | P | ScanTest, PencocokanScanTest | | |
| SS-15 | Unit berubah status sebelum disimpan | Scan PRJ-…, admin lain ubah unit jadi perbaikan, lalu simpan | Ditolak server, status tidak berubah | N | PencocokanScanTest | | |
| SS-16 | Transaksi dibatalkan saat scan | Peminjam membatalkan di tengah proses | Scan berikutnya ditolak "berstatus dibatalkan" | N | PencocokanScanTest | | |
| SS-17 | Ajuan multi-barang lengkap | Proyektor×2 + Laptop×1 dengan salah scan di tengah | Hanya unit valid masuk; selesai → dipinjam | P | PencocokanScanTest | | |

### 4.20 Scan — Pengembalian (SK)

| ID | Skenario | Input | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| SK-01 ★ | Buka dari scan unit | MIC-001 | Pinjaman Dewi terbuka, MIC-001 langsung masuk daftar | P | ScanTest | | |
| SK-02 | Unit tidak sedang dipinjam | PRJ-001 | "Unit PRJ-001 tidak sedang dipinjam." | N | ScanTest | | |
| SK-03 ★ | **QR tidak dikenal** | ZZZ-000 | "QR tidak dikenal: ZZZ-000." | N | ScanTest | | |
| SK-04 ★ | Unit milik pinjaman lain | Pinjaman Dewi terbuka, scan LPT-001 | "…bukan bagian dari peminjaman…" | N | ScanTest, PencocokanScanTest | | |
| SK-05 | Scan ulang | MIC-001 lagi | "Unit MIC-001 sudah di-scan." | N | ScanTest | | |
| SK-06 | Kondisi & catatan | MIC-001 rusak + catatan → Simpan | Unit perbaikan, catatan tersimpan | P | ScanTest | | |
| SK-07 ★ | Sebagian | Simpan MIC-001 saja | Tetap dipinjam; "Belum kembali" MIC-002 | P | ScanTest | | |
| SK-08 | Unit sudah kembali | MIC-001 lagi pada transaksi sama | "…sudah dikembalikan pada …" | N | ScanTest | | |
| SK-09 | Terlambat via scan | LPT-001 → Simpan | Peringatan terlambat; denda Rp2.000, selesai | P | — | | |
| SK-10 | Buka dengan kartu | Kartu Dewi | Pinjaman Dewi terbuka | P | PencocokanScanTest | | |
| SK-11 | Kartu/kode peminjaman sebagai unit | Kartu / PJM saat transaksi terbuka | "bukan QR unit" | N | PencocokanScanTest | | |

### 4.21 Scanner USB 2D & Penanganan Error Scan (SU)
Prasyarat: scanner USB mode keyboard (akhiran Enter, sebagian tes juga dengan akhiran Tab); halaman Scan QR.

| ID | Skenario | Langkah / Data uji | Hasil yang diharapkan | J | Tes otomatis | Hasil aktual | Status |
|---|---|---|---|---|---|---|---|
| SU-01 ★ | Auto-focus saat halaman dibuka | Buka Scan QR | Kursor di kolom scanner; indikator hijau "Scanner siap" | P | — | | |
| SU-02 ★ | Proses otomatis saat Enter | Scan label PRJ-… | Diproses tanpa klik apa pun | P | — | | |
| SU-03 | Scanner berakhiran Tab | Ubah setelan scanner ke suffix Tab | Tetap diproses, fokus tidak pindah | P | — | | |
| SU-04 ★ | Fokus hilang (klik area kosong / tab lain) | Klik area abu-abu, lalu scan | Indikator kuning lalu kembali hijau; hasil scan tetap diproses | P | — | | |
| SU-05 ★ | Fokus di kolom catatan | Ketik "retak" di catatan, lalu scan unit lain | Unit diproses; isi catatan tetap "retak" (tidak tercampur kode) | P | — | | |
| SU-06 | Ketikan manusia di catatan + Enter | Ketik pelan "retak" + Enter | Tidak dianggap scan | N | — | | |
| SU-07 ★ | Cegah scan ganda | Tekan pemicu scanner 2× cepat pada QR sama | Diproses sekali; info "diabaikan (scan ganda)" | N | — | | |
| SU-08 ★ | Dua kode berbeda beruntun cepat | Scan XYZ-999 lalu TRP-001 < 1 detik | Keduanya diproses berurutan (antrean), tidak ada yang hilang | P | — | | |
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
| SU-19 | Library lokal | Matikan internet → buka Scan QR → kamera | Kamera tetap bisa dinyalakan (tanpa CDN) | P | ScanTest | | |

---

## 5. Matriks Ketertelusuran Kebutuhan

| Kode | Kebutuhan (CLAUDE.md) | Kasus uji | Feature Test |
|---|---|---|---|
| R01 | Peran admin & peminjam; semua route dilindungi middleware role | AU-01–AU-12, PJ-09, LP-08, RN-07 | RoleAccessTest, RegistrationTest, *(semua tes hak akses)* |
| R02 | CRUD kategori, lokasi, barang, unit (kode otomatis), peminjam, pengaturan | KT, LK, BR, UN, PJ, PG | KategoriLokasiTest, BarangUnitTest, PeminjamPengaturanTest |
| R03 | Ajuan: barang + jumlah, tujuan, tanggal pinjam & rencana kembali; aturan lama/jumlah/terlambat | AJ-01–AJ-15, PG-05 | AjuanVerifikasiTest |
| R04 | Cek ketersediaan memperhitungkan tabrakan rentang (disetujui/dipinjam), dicek ulang saat disetujui | KS-01–KS-08, AJ-11, VR-03, VR-04 | KetersediaanTest, KetersediaanServiceTest, PersetujuanAjuanTest |
| R05 | Verifikasi: setujui / tolak dengan alasan wajib; notifikasi | VR-01–VR-10, RN-04, RN-08 | AjuanVerifikasiTest, PersetujuanAjuanTest |
| R06 | Pembatalan sebelum diambil | BT-01–BT-04, VR-10 | AjuanVerifikasiTest, PersetujuanAjuanTest |
| R07 | Serah-terima: cek jenis, status tersedia, jumlah; lengkap → dipinjam | ST-01–ST-08, SS-01–SS-17 | SerahPengembalianTest, ScanTest, PencocokanScanTest |
| R08 | Pengembalian: kondisi, tanggal kembali, boleh sebagian | PB-01–PB-10, SK-01–SK-11 | SerahPengembalianTest, ScanTest |
| R09 | Keterlambatan otomatis & denda per unit per hari (0 = tanpa denda) | PB-02–PB-03, PB-06, PB-11–PB-16, RN-01 | SerahPengembalianTest, PerhitunganDendaTest |
| R10 | Kedaluwarsa (scheduler) & pengingat | SC-01–SC-07 | SchedulerNotifikasiLaporanTest |
| R11 | Riwayat, dashboard, laporan PDF/Excel + filter tanggal | RN-02, RN-09, LP-01–LP-08 | SchedulerNotifikasiLaporanTest, PerhitunganDendaTest |
| R12 | QR per unit, cetak label massal, kartu QR peminjam | QR-01–QR-06, KQ-01–KQ-07 | QrLabelTest, ScanTest |
| R13 | Scan kamera (html5-qrcode lokal) & input manual | SS, SK, SU-16–SU-19 | ScanTest |
| R14 | Scanner USB: auto-focus, Enter, cegah scan ganda, pesan kode tidak dikenal | SU-01–SU-15 | PencocokanScanTest (sisi server) |
| R15 | Unit rusak/hilang tidak bisa dipinjam | UN-08–UN-09, KS-05, ST-05, SS-10, PB-05 | BarangUnitTest, KetersediaanServiceTest, ScanTest |
| R16 | Responsif, menu aktif, pesan sukses/gagal jelas | AU-19, KT-06, SS-03 | — (manual) |

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
1. Pastikan scanner dalam mode **USB HID keyboard**, akhiran **Enter** (CR). Uji di Notepad: scan label → teks `PRJ-001` muncul lalu pindah baris.
2. Keyboard layout Windows dan scanner sama-sama **US** (bila tidak, tanda "-" bisa berubah).
3. Buka Scan QR; indikator harus hijau. Jalankan SU-01 s/d SU-15.
4. Bila scanner mengirim prefix simbologi (mis. `]Q1`), aplikasi otomatis membuangnya (SU-10).

### 6.3 Kamera dari HP
1. Laragon → Menu → Apache → SSL → Enabled; buka `https://peminjaman-barang.test` (terima sertifikat lokal).
2. Dari HP di jaringan yang sama, buka alamat IP komputer dengan `https://`.
3. Izinkan kamera; arahkan ke label (jarak ± 15–25 cm, cahaya cukup).

---

## 7. Tes Otomatis (Feature Test)

Jalankan: `php artisan test` (database terpisah `peminjaman_barang_test`). Hasil Sprint 5: **182 tes lulus (924 assertion)**.

| Kelas tes | Jumlah | Cakupan |
|---|---|---|
| Auth\* (Breeze) + ProfileTest | 25 | Login, registrasi, reset/ubah password, profil |
| RoleAccessTest | 8 | Middleware role, akun nonaktif, redirect sesuai peran |
| Admin\KategoriLokasiTest | 6 | CRUD + larangan hapus + validasi |
| Admin\BarangUnitTest | 13 | Barang, foto, kode unit otomatis, kondisi/status unit |
| Admin\PeminjamPengaturanTest | 10 | Data peminjam, reset password, pengaturan |
| Transaksi\KetersediaanTest | 9 | Rumus tabrakan rentang & status |
| Transaksi\AjuanVerifikasiTest | 15 | Validasi ajuan, setujui/tolak, pembatalan |
| Transaksi\SerahPengembalianTest | 19 | Serah-terima, pengembalian, denda |
| Transaksi\SchedulerNotifikasiLaporanTest | 10 | Kedaluwarsa, pengingat, notifikasi, laporan, dashboard |
| Scan\QrLabelTest | 4 | QR SVG, label massal, kartu bertanda tangan |
| Scan\ScanTest | 18 | Buka transaksi & pencocokan scan |
| **KetersediaanServiceTest** (Sprint 5) | 6 | Penjumlahan tabrakan, multi-barang, unit rusak, terlambat memblokir, uji acak 200 skenario |
| **PersetujuanAjuanTest** (Sprint 5) | 9 | Atomik, overbooking, terlambat, persetujuan ganda, admin nonaktif, alasan spasi |
| **PencocokanScanTest** (Sprint 5) | 15 | Normalisasi kode scanner, panjang, alur multi-barang, race condition, kartu admin |
| **PerhitunganDendaTest** (Sprint 5) | 13 | Kalender (lintas bulan/tahun/kabisat), unit hilang, perkiraan, tarif berubah, laporan |
| ExampleTest (Feature + Unit) | 2 | Redirect beranda |

---

## 8. Log Cacat

| ID | Tanggal | Kasus uji | Deskripsi | Tingkat (Kritis/Tinggi/Sedang/Rendah) | Status | Perbaikan / commit |
|---|---|---|---|---|---|---|
| D-01 | Sprint 5 | KS-06 | Pinjaman terlambat hanya memblokir stok s/d hari ini, sehingga ajuan untuk hari-hari berikutnya bisa disetujui walau unit belum kembali | Tinggi | Selesai | Aturan diubah: terlambat memblokir semua tanggal ke depan (KetersediaanService) |
| D-02 | Sprint 5 | SU-08 | Label "memproses…" di indikator scanner kadang tetap tampil setelah proses selesai | Rendah | Selesai | Ganti `x-show` dengan `x-text` |
| | | | | | | |

---

## 9. Laporan Penutupan Pengujian

| Metrik | Nilai |
|---|---|
| Jumlah kasus uji manual | 217 (72 prioritas Tinggi ★) |
| Dieksekusi | ____ |
| Lulus / Gagal / Tertunda | ____ / ____ / ____ |
| Persentase lulus | ____ % |
| Kasus prioritas Tinggi (★) lulus | ____ / 72 |
| Feature Test otomatis | 182 lulus / 0 gagal |
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
