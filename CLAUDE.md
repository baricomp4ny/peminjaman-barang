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
Mitra: Laboratorium Kultur Jaringan, Sekolah Vokasi IPB University (web khusus peminjaman alat lab). Peminjaman barang masih manual; sulit melacak peminjam, ketersediaan, dan keterlambatan.
Metode: Agile Scrum, 5 sprint. Tim 3 orang.

## Peran
- admin: verifikasi ajuan, scan serah-terima & pengembalian, kelola data, laporan, pengaturan
- peminjam (TANPA login, diubah setelah Sprint 5): lihat katalog, ajukan peminjaman dengan mengisi identitas (nama, NIM/NIP, email, no. HP, unit), cek status & batalkan dengan kode peminjaman + NIM atau tautan email bertanda tangan
Login hanya untuk admin. Semua route /admin dilindungi middleware role:admin. Menyembunyikan menu saja tidak cukup.

## Alur sistem
1. Peminjam mengajukan (halaman publik, tanpa login): isi identitas, pilih jenis barang + jumlah, tujuan, tanggal pinjam, tanggal rencana kembali. Peminjam menerima kode PJM-… + QR-nya (layar & email).
2. Admin memverifikasi: identitas, ketersediaan pada rentang tanggal, aturan peminjaman.
3. Disetujui, atau ditolak dengan alasan wajib. Peminjam mendapat notifikasi lewat email.
4. Serah-terima: admin scan QR kode peminjaman (dari layar/email peminjam), cocokkan kartu identitas asli, lalu scan QR setiap unit. Sistem cek jenis unit sesuai ajuan, status unit tersedia, jumlah tidak melebihi yang disetujui. Setelah lengkap → status Dipinjam, unit → dipinjam.
5. Pengembalian: admin scan QR unit, pilih kondisi (baik/rusak/hilang), catat tanggal kembali. Boleh sebagian.
6. Sistem memperbarui status unit, riwayat, keterlambatan, denda, dan laporan.
Cabang: ajuan disetujui tapi tidak diambil dalam [1] hari → kedaluwarsa (scheduler); peminjam boleh membatalkan sebelum diambil; unit rusak/hilang tidak bisa dipinjam.

## Aturan bisnis (disimpan di tabel pengaturan, bisa diubah admin)
- Lama pinjam maksimal [7] hari; maksimal [3] unit per ajuan
- Denda Rp[1.000] per unit per hari (0 = tanpa denda)
- Peminjam yang punya pinjaman terlambat tidak boleh mengajukan baru
- Cek ketersediaan WAJIB menghitung ajuan berstatus disetujui/dipinjam yang rentang tanggalnya bertabrakan (mulai_A <= selesai_B dan selesai_A >= mulai_B), bukan hanya stok hari ini. Cek ulang di dalam DB::transaction saat admin menyetujui.

## Database
Semua primary key ULID (tanpa auto increment), foreign key `foreignUlid`.
- users: name, email, password (nullable — peminjam tanpa password), role (admin, peminjam), nomor_identitas, unit, no_hp, status (aktif, nonaktif). Peminjam dicocokkan berdasarkan nomor_identitas.
- kategori: nama
- lokasi: nama, keterangan
- barang: kategori_id, kode (prefix kode unit, unik, misal PRJ — ditambahkan Sprint 2), nama, deskripsi, foto
- unit_barang: barang_id, lokasi_id, kode_unit (unik, isi QR, otomatis, contoh PRJ-001), kondisi (baik, rusak, hilang), status (tersedia, dipinjam, perbaikan, hilang), tanggal_perolehan
- peminjaman: kode_peminjaman (unik), user_id, tujuan, tanggal_pinjam, tanggal_rencana_kembali, status (menunggu, disetujui, ditolak, dipinjam, selesai, dibatalkan, kedaluwarsa), alasan_penolakan, diverifikasi_oleh, diverifikasi_pada, diserahkan_pada
- peminjaman_detail: peminjaman_id, barang_id, jumlah
- peminjaman_unit: peminjaman_id, unit_barang_id, dikembalikan_pada, kondisi_kembali, hari_terlambat, denda, catatan
- pengaturan: key, value
"Terlambat" dihitung otomatis, bukan kolom.
Seeder: 2 admin (admin@, petugas@), 5 peminjam tanpa akun login (NIM format SV IPB, mis. J0301201001), 4 kategori alat lab (Alat Ukur & Analisis, Peralatan Inokulasi, Persiapan Media & Sterilisasi, Wadah & Gelas Kultur), 3 lokasi (Ruang Persiapan Media, Ruang Tanam Aseptik, Gudang Alat), 10 jenis alat kultur jaringan (MKR, PHM, HPS, TBA, BNS, PSN, MPP, CTP, ERL, STR), 30 unit, contoh transaksi di semua status.

## Menu
Admin (sidebar, urutan & deskripsi sebagai subjudul halaman):
- Dashboard — Ringkasan inventaris dan transaksi aktif
- Data Barang (Jenis Barang, Unit & Kondisi, Kategori, Lokasi, Cetak Label Barcode) — Pengelolaan data barang, jenis, kategori, unit, kondisi, lokasi, serta barcode
- Data Peminjam — Pengelolaan identitas dan riwayat peminjaman setiap peminjam
- Transaksi (Ajuan Peminjaman + badge, Peminjaman Aktif, Pengembalian, Riwayat Transaksi) — Pengelolaan pengajuan, peminjaman aktif, pengembalian, dan riwayat transaksi
- Scan Barcode — Pemindaian barcode untuk mencatat penyerahan dan pengembalian barang
- Laporan (Transaksi, Pengembalian, Inventaris, Keterlambatan, Denda — PDF/Excel, filter tanggal) — Rekapitulasi transaksi, pengembalian, inventaris, keterlambatan, dan denda
- Pengaturan (Data Admin, Aturan & Denda) — Pengelolaan data admin, aturan peminjaman, dan ketentuan denda
- avatar: profil, ganti password, logout
Peminjam (navbar publik): Katalog Barang, Ajukan Peminjaman, Cek Status; tautan Login Admin.
Teknologi label tetap QR Code (dibaca scanner 2D & kamera); di menu disebut "barcode".
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

## Catatan desain (disetujui di Sprint 3)
- Lama pinjam = tanggal rencana kembali − tanggal pinjam (tgl 1 → tgl 8 = 7 hari).
- Ketersediaan (KetersediaanService): kapasitas = unit kondisi baik berstatus tersedia/dipinjam; terpakai = jumlah ajuan "disetujui" yang bertabrakan + unit BELUM kembali dari pinjaman "dipinjam" yang bertabrakan. Diubah di Sprint 5 (disetujui): pinjaman yang sudah terlambat memblokir SEMUA tanggal ke depan sampai unitnya kembali.
- Batas ambil = min(tanggal pinjam + batas_ambil_hari, rencana kembali); lewat itu → kedaluwarsa (peminjaman:kedaluwarsa 00:05). Pengingat H-1: peminjaman:pengingat 07:00, tidak dobel. Keduanya punya opsi --tanggal untuk uji manual.
- Serah-terima Sprint 3 lewat form pilih/ketik kode unit; Sprint 4 (scan QR) memanggil PeminjamanService::cekUnitUntukSerah() dan serahkan() yang sama.
- Denda disimpan per unit saat pengembalian dicatat (tarif saat itu); perubahan tarif tidak mengubah denda lama.
- Nama mitra untuk kop laporan/email: MITRA_NAMA di .env (config/peminjaman.php).

## Catatan desain (disetujui di Sprint 4)
- Isi QR unit = kode_unit. (Kartu QR peminjam PMJ.… dihapus — lihat catatan clean code.)
- Halaman /admin/scan: mode serah|kembali. Langkah 1 ScanService::cari() (PJM-… atau kode unit untuk pengembalian); langkah 2 ScanService::cekUnit() per scan (JSON, daftar unit disimpan di browser); langkah 3 form ke endpoint serahkan/pengembalian.store yang sama dengan form (dari_scan=1 → kembali ke halaman scan).
- html5-qrcode 2.3.8 disimpan di public/vendor/html5-qrcode (bukan CDN). Kamera butuh HTTPS atau localhost.
- Debounce kamera: kode sama diabaikan 2,5 detik.

## Catatan desain (Sprint 5)
- Logika halaman scan ada di resources/js/scan-qr.js, didaftarkan via Alpine.data('halamanScan') di app.js (harus sebelum Alpine.start()).
- Scanner USB: penangkap keydown global (ketikan < 50 ms/karakter + Enter/Tab = scan) walau fokus di luar input; antrean berurutan; debounce 2,5 detik untuk kamera & scanner (ketik manual tidak); indikator "Scanner siap".
- Normalisasi kode (browser & ScanService::normalisasi): buang karakter kontrol, prefix AIM "]Xn", spasi; huruf besar; maks 100 karakter.
- Dokumen pengujian: docs/STLC.md.

## Catatan desain (perubahan setelah Sprint 5, disetujui)
- Peminjam tanpa login: route publik /, /katalog/{barang}, /ajuan, /status, /status/{kode} (nama route peminjam.*). Registrasi dihapus; login & reset password hanya role admin.
- IdentitasPeminjamService: NIM terdaftar → email harus sama (dicegah pengalihan email); NIM baru → email belum dipakai; peminjam nonaktif ditolak; nama/email tidak ditimpa dari form, unit & no. HP diperbarui.
- Akses halaman status: kode + NIM (StatusController, disimpan di session), tautan URL::signedRoute di email, atau langsung setelah mengirim ajuan. POST /ajuan & /status dibatasi throttle 10/menit + honeypot "website".
- Notifikasi peminjam hanya email (+ AjuanTerkirim berisi kode); tetap dicatat di tabel notifications.
- Pengaturan > Data Admin (AkunAdminController, {akunAdmin} hanya role admin): tidak bisa menonaktifkan/menghapus diri sendiri, minimal satu admin aktif, admin yang pernah memverifikasi tidak bisa dihapus.
- Kartu QR peminjam diganti QR kode peminjaman.

## Catatan desain tampilan (disetujui)
- Warna tunggal: gradasi Marian Blue, skala `marian-50 … marian-950` (#263C92 = marian-800) di tailwind.config.js; `indigo` dipetakan ke skala yang sama. Merah hanya untuk pesan gagal/ditolak/hilang.
- Gaya halaman publik mengikuti referensi kartu membulat (rounded-4xl/5xl, shadow-lembut): navbar pil, hero gradasi + ilustrasi SVG kultur jaringan (ganti foto: taruh public/images/hero-lab.jpg), kartu fitur, kartu aturan, kategori bento, katalog `x-kartu-alat`, banner alur, footer gradasi.
- Card dikurangi (disetujui): fitur beranda = ikon + teks tanpa kotak; aturan + cek status = satu pita gradasi; kartu kategori bento dihapus; langkah alur tanpa kotak; detail alat & halaman status = satu panel (`x-detail-peminjaman polos`); ringkasan form ajuan satu kartu; dashboard admin = satu strip statistik bersekat. Kartu alat katalog tetap.
- Katalog di beranda (#katalog, tanpa halaman terpisah) bergaya etalase toko: sidebar filter kiri (Cari nama/kode, Cek Tanggal mulai–selesai → sisa unit dihitung KetersediaanService untuk rentang itu, Hanya yang tersedia, Kategori + jumlah, Lokasi, Paling Sering Dipinjam = top 3 jumlah unit dari pinjaman dipinjam/selesai), bilah "Menampilkan x–y dari n alat" + Urutkan (nama, nama_desc, sisa), grid 9 alat/halaman. Validasi di Peminjam\KatalogRequest; filter tersedia & urut sisa dikerjakan di koleksi lalu LengthAwarePaginator. Di HP sidebar jadi tombol "Filter".
- Foto jenis barang dipotong di browser sebelum diunggah (Cropper.js 1.6.2 via npm/Vite, resources/js/potong-foto.js, Alpine.data('potongFoto')): bentuk persegi (default, sesuai kartu katalog) / 4:3 / bebas, zoom, putar; hasil JPEG maks 1200 px menggantikan file di input. Validasi server tetap BarangRequest.
- Palet 80/15/5: Marian Blue (utama), Gold `gold` #FED80B (15%), Vivid Magenta `magenta` #E01184 (5%) di tailwind.config.js. Nuansa per section beranda: hero = gradasi biru tua; fitur = panel putih + ikon emas; aturan + cek status = pita emas; katalog = latar biru muda; cara meminjam = biru tua + nomor langkah magenta. Kartu alat: outline magenta saat hover/klik.
- Responsif & fleksibel: grid `repeat(auto-fit|auto-fill, minmax(min(100%,Xrem),1fr))` dan ukuran huruf `clamp()`; tanpa scroll horizontal di 360 px.
- Komponen: `x-ikon-alat` (ikon garis per jenis alat/kategori), `x-ilustrasi-lab`, `x-kartu-alat`. Font: Plus Jakarta Sans (bunny.net).
- Info lab di config/peminjaman.php: MITRA_NAMA, MITRA_SINGKAT, MITRA_ALAMAT, MITRA_JAM_LAYANAN.

## Catatan desain (clean code, disetujui)
- Primary key ULID (trait HasUlids) di semua tabel aplikasi; validasi id memakai rule `ulid`. Tabel notifications: uuid + ulidMorphs. Hanya tabel internal `migrations` yang tetap auto increment.
- Field ganda dihapus: `peminjaman_unit.diserahkan_pada` (waktu serah-terima cukup di `peminjaman.diserahkan_pada`).
- Nilai status/peran memakai PHP Enum di app/Enums (Peran, StatusAkun, StatusPeminjaman, StatusUnit, KondisiUnit) + cast Eloquent; Form Request memakai Rule::enum. Bandingkan dengan Enum, bukan string.
- Kartu QR peminjam PMJ.… beserta logikanya dihapus; kode PMJ lama dianggap "QR tidak dikenal".
- Menu sidebar admin disiapkan App\View\Composers\AdminLayoutComposer (didaftarkan di AppServiceProvider).
- Helper global `rupiah()` di app/helpers.php (autoload files composer).
- Kode mati dihapus: Sanctum + routes/api.php, verifikasi email & konfirmasi password Breeze, failed_jobs/personal_access_tokens.
- Format kode: Laravel Pint (`vendor\bin\pint`).
