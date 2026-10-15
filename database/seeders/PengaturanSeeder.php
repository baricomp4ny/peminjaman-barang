<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

class PengaturanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Pengaturan::DEFAULT as $key => $value) {
            Pengaturan::atur($key, $value);
        }
    }
}
