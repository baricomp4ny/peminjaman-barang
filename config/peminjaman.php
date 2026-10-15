<?php

return [
    // Nama mitra untuk kop laporan, email, dan tampilan publik. Isi di .env.
    'mitra' => env('MITRA_NAMA', 'Laboratorium Kultur Jaringan Sekolah Vokasi IPB University'),

    // Nama singkat untuk navbar/judul (layar sempit)
    'mitra_singkat' => env('MITRA_SINGKAT', 'Lab Kultur Jaringan SV IPB'),

    // Info kontak di footer halaman publik (ubah di .env bila perlu)
    'alamat' => env('MITRA_ALAMAT', 'Sekolah Vokasi IPB University, Jl. Kumbang No. 14, Bogor'),
    'jam_layanan' => env('MITRA_JAM_LAYANAN', 'Senin–Jumat, 08.00–15.00 WIB'),
];
