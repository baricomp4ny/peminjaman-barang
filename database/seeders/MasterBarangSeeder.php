<?php

namespace Database\Seeders;

use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\UnitBarang;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Data contoh alat Laboratorium Kultur Jaringan (10 jenis, 30 unit). */
class MasterBarangSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = collect(['Alat Ukur & Analisis', 'Peralatan Inokulasi', 'Persiapan Media & Sterilisasi', 'Wadah & Gelas Kultur'])
            ->mapWithKeys(fn ($nama) => [$nama => Kategori::create(['nama' => $nama])]);

        $lokasi = collect([
            ['Ruang Persiapan Media', 'Lantai 1, ruang pembuatan media & sterilisasi'],
            ['Ruang Tanam Aseptik', 'Lantai 1, ruang transfer/inokulasi (LAF)'],
            ['Gudang Alat', 'Lantai 1, lemari alat & gelas'],
        ])->mapWithKeys(fn ($l) => [$l[0] => Lokasi::create(['nama' => $l[0], 'keterangan' => $l[1]])]);

        // [kategori, nama, prefix kode unit, jumlah unit, lokasi, deskripsi] — total 30 unit
        $daftar = [
            ['Alat Ukur & Analisis', 'Mikroskop Stereo', 'MKR', 4, 'Gudang Alat', 'Mikroskop stereo pembesaran 7–45× untuk mengamati eksplan, kalus, dan planlet.'],
            ['Alat Ukur & Analisis', 'pH Meter Digital', 'PHM', 4, 'Ruang Persiapan Media', 'pH meter digital dengan elektroda dan larutan buffer kalibrasi pH 4, 7, 10.'],
            ['Persiapan Media & Sterilisasi', 'Hot Plate Stirrer', 'HPS', 3, 'Ruang Persiapan Media', 'Pemanas sekaligus pengaduk magnetik untuk melarutkan media MS dan agar.'],
            ['Alat Ukur & Analisis', 'Timbangan Analitik', 'TBA', 3, 'Ruang Persiapan Media', 'Timbangan analitik ketelitian 0,1 mg untuk menimbang bahan kimia dan ZPT.'],
            ['Peralatan Inokulasi', 'Bunsen Burner', 'BNS', 3, 'Ruang Tanam Aseptik', 'Pembakar spiritus untuk sterilisasi alat diseksi di dalam Laminar Air Flow.'],
            ['Peralatan Inokulasi', 'Set Pinset & Scalpel', 'PSN', 3, 'Ruang Tanam Aseptik', 'Satu set pinset panjang, gagang scalpel, dan gunting untuk memotong eksplan.'],
            ['Alat Ukur & Analisis', 'Mikropipet', 'MPP', 2, 'Ruang Persiapan Media', 'Mikropipet volume variabel 100–1000 µL untuk menambahkan zat pengatur tumbuh.'],
            ['Wadah & Gelas Kultur', 'Set Cawan Petri', 'CTP', 3, 'Gudang Alat', 'Satu set 20 cawan petri kaca diameter 90 mm untuk sterilisasi dan pemotongan eksplan.'],
            ['Wadah & Gelas Kultur', 'Set Erlenmeyer', 'ERL', 2, 'Gudang Alat', 'Satu set erlenmeyer 250 mL dan 500 mL untuk pembuatan larutan stok dan media.'],
            ['Persiapan Media & Sterilisasi', 'Bead Sterilizer', 'STR', 3, 'Ruang Tanam Aseptik', 'Sterilisator manik kaca (glass bead) untuk sterilisasi cepat pinset dan scalpel.'],
        ];

        foreach ($daftar as [$namaKategori, $nama, $prefix, $jumlah, $namaLokasi, $deskripsi]) {
            $barang = Barang::create([
                'kategori_id' => $kategori[$namaKategori]->id,
                'kode' => $prefix,
                'nama' => $nama,
                'deskripsi' => $deskripsi,
                'foto' => $this->foto($prefix),
            ]);

            for ($n = 1; $n <= $jumlah; $n++) {
                UnitBarang::create([
                    'barang_id' => $barang->id,
                    'lokasi_id' => $lokasi[$namaLokasi]->id,
                    'kode_unit' => sprintf('%s-%03d', $prefix, $n), // contoh MKR-001
                    'kondisi' => KondisiUnit::Baik,
                    'status' => StatusUnit::Tersedia,
                    'tanggal_perolehan' => now()->subMonths(rand(3, 36))->startOfMonth(),
                ]);
            }
        }

        // Contoh unit yang tidak bisa dipinjam
        UnitBarang::where('kode_unit', 'HPS-003')->update([
            'kondisi' => KondisiUnit::Rusak, 'status' => StatusUnit::Perbaikan,
        ]);
        UnitBarang::where('kode_unit', 'MPP-002')->update([
            'kondisi' => KondisiUnit::Hilang, 'status' => StatusUnit::Hilang,
        ]);
    }

    /**
     * Foto contoh disimpan di database/seeders/foto/{KODE}.jpg (ikut git) lalu disalin ke disk public,
     * supaya migrate:fresh --seed tidak menghilangkan foto alat.
     */
    private function foto(string $kode): ?string
    {
        $sumber = database_path("seeders/foto/{$kode}.jpg");
        if (! is_file($sumber)) {
            return null;
        }

        $tujuan = "barang/{$kode}.jpg";
        Storage::disk('public')->put($tujuan, file_get_contents($sumber));

        return $tujuan;
    }
}
