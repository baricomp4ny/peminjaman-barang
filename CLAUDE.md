# CLAUDE.md — Sistem Informasi Peminjaman Barang Berbasis Web dengan QR Code

## Aturan kerja
- Gunakan bahasa Indonesia, penjelasan sederhana, kerjakan bertahap.
- Kerjakan HANYA sprint yang diminta. Jangan mengubah konsep, alur, atau struktur database tanpa bertanya dulu.
- Sebelum menulis kode di setiap sprint, tampilkan rencana singkat (file yang akan dibuat/diubah) dan tunggu persetujuan.
- Setelah setiap sprint: jalankan `php artisan migrate:fresh --seed` dan `php artisan test`, pastikan tidak error, lalu buat git commit dengan pesan "Sprint N: <ringkasan>".
- Jangan pernah commit file .env.
- Beri komentar singkat bahasa Indonesia di bagian logika penting.

## Lingkungan
- Windows + Laragon, PHP 8.1+, MySQL, Composer, Node.js
- Laravel 10.x (install dengan: composer create-project laravel/laravel:^10.0 .)
- URL lokal: http://peminjaman-barang.test, database: peminjaman_barang (user root, tanpa password)
- MAIL_MAILER=log untuk uji lokal

## Paket
- laravel/breeze (Blade + Tailwind) untuk autentikasi
- simplesoftwareio/simple-qrcode untuk generate QR
- html5-qrcode (JS) untuk scan via kamera — simpan lokal di public/vendor, bukan CDN
- barryvdh/laravel-dompdf (PDF), maatwebsite/excel (Excel)
- spatie/laravel-activitylog (audit log, opsional)

## Konteks
Mitra: [nama laboratorium/unit/organisasi]. Peminjaman barang masih manual; sulit melacak peminjam, ketersediaan, dan keterlambatan.
Metode: Agile Scrum, 5 sprint. Tim 3 orang.

## Peran
- admin: verifikasi ajuan, scan serah-terima & pengembalian, kelola data, laporan, pengaturan
- peminjam (punya akun): ajukan peminjaman, lihat status & riwayat, kartu QR milik sendiri
Semua route dilindungi middleware role. Menyembunyikan menu saja tidak cukup.

## Alur sistem
1. Peminjam mengajukan: pilih jenis barang + jumlah, tujuan, tanggal pinjam, tanggal rencana kembali.
2. Admin memverifikasi: identitas, ketersediaan pada rentang tanggal, aturan peminjaman.
3. Disetujui, atau ditolak dengan alasan wajib. Peminjam mendapat notifikasi.
4. Serah-terima: admin scan QR setiap unit. Sistem cek jenis unit sesuai ajuan, status unit tersedia, jumlah tidak melebihi yang disetujui. Setelah lengkap → status Dipinjam, unit → dipinjam.
5. Pengembalian: admin scan QR unit, pilih kondisi (baik/rusak/hilang), catat tanggal kembali. Boleh sebagian.
6. Sistem memperbarui status unit, riwayat, keterlambatan, denda, dan laporan.
Cabang: ajuan disetujui tapi tidak diambil dalam [1] hari → kedaluwarsa (scheduler); peminjam boleh membatalkan sebelum diambil; unit rusak/hilang tidak bisa dipinjam.

## Aturan bisnis (disimpan di tabel pengaturan, bisa diubah admin)
- Lama pinjam maksimal [7] hari; maksimal [3] unit per ajuan
- Denda Rp[1.000] per unit per hari (0 = tanpa denda)
- Peminjam yang punya pinjaman terlambat tidak boleh mengajukan baru
- Cek ketersediaan WAJIB menghitung ajuan berstatus disetujui/dipinjam yang rentang tanggalnya bertabrakan (mulai_A <= selesai_B dan selesai_A >= mulai_B), bukan hanya stok hari ini. Cek ulang di dalam DB::transaction saat admin menyetujui.

## Database
- users: name, email, password, role (admin, peminjam), nomor_identitas, unit, no_hp, status (aktif, nonaktif)
- kategori: nama
- lokasi: nama, keterangan
- barang: kategori_id, nama, deskripsi, foto
- unit_barang: barang_id, lokasi_id, kode_unit (unik, isi QR, otomatis, contoh PRJ-001), kondisi (baik, rusak, hilang), status (tersedia, dipinjam, perbaikan, hilang), tanggal_perolehan
- peminjaman: kode_peminjaman (unik), user_id, tujuan, tanggal_pinjam, tanggal_rencana_kembali, status (menunggu, disetujui, ditolak, dipinjam, selesai, dibatalkan, kedaluwarsa), alasan_penolakan, diverifikasi_oleh, diverifikasi_pada, diserahkan_pada
- peminjaman_detail: peminjaman_id, barang_id, jumlah
- peminjaman_unit: peminjaman_id, unit_barang_id, diserahkan_pada, dikembalikan_pada, kondisi_kembali, hari_terlambat, denda, catatan
- pengaturan: key, value
"Terlambat" dihitung otomatis, bukan kolom.
Seeder: 1 admin, 5 peminjam, 4 kategori, 3 lokasi, 10 jenis barang, 30 unit, contoh transaksi di semua status.

## Menu
Admin (sidebar): Dashboard; Scan QR Code; Transaksi (Ajuan Peminjaman + badge, Peminjaman, Pengembalian, Riwayat); Data Barang (jenis, unit, kategori, lokasi, cetak label QR); Data Peminjam; Laporan (peminjaman, pengembalian, inventaris, keterlambatan, denda — PDF/Excel, filter tanggal); Pengaturan; avatar (profil, ganti password, logout).
Peminjam (navbar): Beranda, Katalog Barang, Ajukan Peminjaman, Pinjaman Saya, Notifikasi, Profil (kartu QR, logout).
Responsif, menu aktif ditandai, pesan sukses/gagal jelas.

## Arsitektur
- Controller per peran: app/Http/Controllers/Admin, app/Http/Controllers/Peminjam
- Service: KetersediaanService, PeminjamanService (ajukan, setujui, tolak, serahkan, kembalikan — semua dalam DB::transaction), DendaService
- Validasi di Form Request; otorisasi dengan middleware/Policy; eager loading untuk hindari N+1
- Input scanner USB: auto-focus, proses otomatis saat Enter, cegah scan ganda, tampilkan pesan jika kode tidak dikenal

## Sprint
1. Analisis & perancangan: install Laravel 10 + Breeze, migration + model + relasi + seeder, middleware role, layout admin & peminjam, dashboard kosong
2. Web dasar: CRUD kategori, lokasi, barang, unit (kode otomatis), peminjam, pengaturan
3. Transaksi & laporan: ajuan, verifikasi, pembatalan, pengembalian, keterlambatan & denda, riwayat, dashboard, laporan PDF/Excel, notifikasi + scheduler
4. QR Code & scan: generate QR per unit, cetak label massal, kartu QR peminjam, scan kamera, input manual, pencocokan scan dengan ajuan
5. Hardware & pengujian: input scanner USB, penanganan error scan, Feature Test untuk ketersediaan, persetujuan, pencocokan scan, dan denda

## Catatan desain (disetujui di Sprint 1)
- Registrasi publik selalu role `peminjam`. Middleware `role` → 403 jika role tidak cocok; akun `nonaktif` otomatis logout.
- Prefix route: `/admin` (name `admin.`), `/peminjam` (name `peminjam.`). `/dashboard` redirect sesuai role.
- Kunci pengaturan: lama_pinjam_maks, unit_maks_per_ajuan, denda_per_hari, batas_ambil_hari.
- Tabel `notifications` ditambahkan di Sprint 3.
