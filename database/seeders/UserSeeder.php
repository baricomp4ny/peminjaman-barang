<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Semua akun hasil seeder memakai password: password
        User::factory()->admin()->create([
            'name' => 'Administrator',
            'email' => 'admin@peminjaman.test',
            'no_hp' => '081200000000',
        ]);

        $peminjam = [
            ['Andi Pratama', 'andi@peminjaman.test', '2201001', 'Teknik Informatika'],
            ['Budi Santoso', 'budi@peminjaman.test', '2201002', 'Sistem Informasi'],
            ['Citra Lestari', 'citra@peminjaman.test', '2201003', 'Teknik Informatika'],
            ['Dewi Anggraini', 'dewi@peminjaman.test', '2201004', 'Manajemen'],
            ['Eko Saputra', 'eko@peminjaman.test', '2201005', 'Akuntansi'],
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
