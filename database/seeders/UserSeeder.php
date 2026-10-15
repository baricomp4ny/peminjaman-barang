<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun admin (login) memakai password: password
        User::factory()->admin()->create([
            'name' => 'Administrator',
            'email' => 'admin@peminjaman.test',
            'no_hp' => '081200000000',
        ]);
        User::factory()->admin()->create([
            'name' => 'Petugas Lab',
            'email' => 'petugas@peminjaman.test',
            'no_hp' => '081200000001',
        ]);

        // Peminjam tidak punya akun login; data identitasnya tersimpan dari form ajuan publik

        $peminjam = [
            ['Andi Pratama', 'andi@peminjaman.test', 'J0301201001', 'Teknologi Industri Benih'],
            ['Budi Santoso', 'budi@peminjaman.test', 'J0301201002', 'Teknologi Industri Benih'],
            ['Citra Lestari', 'citra@peminjaman.test', 'J0302201003', 'Teknologi Produksi dan Pengembangan Masyarakat Pertanian'],
            ['Dewi Anggraini', 'dewi@peminjaman.test', 'J0303201004', 'Manajemen Agribisnis'],
            ['Eko Saputra', 'eko@peminjaman.test', 'J0304201005', 'Analisis Kimia'],
        ];

        foreach ($peminjam as [$nama, $email, $nim, $unit]) {
            User::factory()->create([
                'name' => $nama,
                'email' => $email,
                'nomor_identitas' => $nim,
                'unit' => $unit,
            ]);
        }
    }
}
