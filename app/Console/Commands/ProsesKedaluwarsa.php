<?php

namespace App\Console\Commands;

use App\Services\PeminjamanService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProsesKedaluwarsa extends Command
{
    protected $signature = 'peminjaman:kedaluwarsa
                            {--tanggal= : Simulasikan "hari ini" (format YYYY-MM-DD), untuk uji manual}';

    protected $description = 'Ubah ajuan disetujui yang tidak diambil sampai batas waktu menjadi kedaluwarsa';

    public function handle(PeminjamanService $service): int
    {
        $hariIni = $this->option('tanggal') ? Carbon::parse($this->option('tanggal')) : today();

        $jumlah = $service->prosesKedaluwarsa($hariIni);

        $this->info("Tanggal acuan {$hariIni->toDateString()}: {$jumlah} ajuan diubah menjadi kedaluwarsa.");

        return self::SUCCESS;
    }
}
