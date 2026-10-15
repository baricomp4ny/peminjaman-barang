<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\UnitBarang;
use Illuminate\Database\Seeder;

class MasterBarangSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = collect(['Elektronik', 'Fotografi', 'Alat Laboratorium', 'Perlengkapan Acara'])
            ->mapWithKeys(fn ($nama) => [$nama => Kategori::create(['nama' => $nama])]);

        $lokasi = collect([
            ['Gudang Utama', 'Lantai 1, ruang 101'],
            ['Laboratorium Komputer', 'Lantai 2, ruang 201'],
            ['Ruang Multimedia', 'Lantai 3, ruang 305'],
        ])->map(fn ($l) => Lokasi::create(['nama' => $l[0], 'keterangan' => $l[1]]))->values();

        // [kategori, nama, prefix kode unit, jumlah unit, deskripsi] — total 30 unit
        $daftar = [
            ['Elektronik', 'Proyektor', 'PRJ', 4, 'Proyektor LCD 3000 lumen, lengkap dengan kabel HDMI.'],
            ['Elektronik', 'Laptop', 'LPT', 4, 'Laptop Core i5, RAM 8 GB, untuk presentasi dan praktikum.'],
            ['Elektronik', 'Speaker Portable', 'SPK', 3, 'Speaker aktif portable dengan baterai isi ulang.'],
            ['Fotografi', 'Kamera DSLR', 'KMR', 3, 'Kamera DSLR dengan lensa kit 18-55 mm.'],
            ['Fotografi', 'Tripod', 'TRP', 3, 'Tripod aluminium tinggi maksimal 160 cm.'],
            ['Alat Laboratorium', 'Multimeter Digital', 'MLT', 3, 'Multimeter digital untuk praktikum elektronika.'],
            ['Alat Laboratorium', 'Solder Station', 'SLD', 2, 'Solder station dengan pengatur suhu.'],
            ['Perlengkapan Acara', 'Mikrofon Wireless', 'MIC', 3, 'Satu set mikrofon wireless dan receiver.'],
            ['Perlengkapan Acara', 'Layar Proyektor', 'LYR', 2, 'Layar proyektor tripod 70 inci.'],
            ['Perlengkapan Acara', 'Kabel Roll', 'KBL', 3, 'Kabel roll 10 meter, 4 lubang.'],
        ];

        foreach ($daftar as $i => [$namaKategori, $nama, $prefix, $jumlah, $deskripsi]) {
            $barang = Barang::create([
                'kategori_id' => $kategori[$namaKategori]->id,
                'kode' => $prefix,
                'nama' => $nama,
                'deskripsi' => $deskripsi,
            ]);

            for ($n = 1; $n <= $jumlah; $n++) {
                UnitBarang::create([
                    'barang_id' => $barang->id,
                    'lokasi_id' => $lokasi[$i % $lokasi->count()]->id,
                    'kode_unit' => sprintf('%s-%03d', $prefix, $n), // contoh PRJ-001
                    'kondisi' => UnitBarang::KONDISI_BAIK,
                    'status' => UnitBarang::STATUS_TERSEDIA,
                    'tanggal_perolehan' => now()->subMonths(rand(3, 36))->startOfMonth(),
                ]);
            }
        }

        // Contoh unit yang tidak bisa dipinjam
        UnitBarang::where('kode_unit', 'SPK-003')->update([
            'kondisi' => UnitBarang::KONDISI_RUSAK, 'status' => UnitBarang::STATUS_PERBAIKAN,
        ]);
        UnitBarang::where('kode_unit', 'SLD-002')->update([
            'kondisi' => UnitBarang::KONDISI_HILANG, 'status' => UnitBarang::STATUS_HILANG,
        ]);
    }
}
