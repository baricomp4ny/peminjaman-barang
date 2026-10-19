<?php

namespace App\Console\Commands;

use App\Services\PeminjamanService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class KirimPengingat extends Command
{
    protected $signature = 'peminjaman:pengingat
                            {--tanggal= : Simulasikan "hari ini" (format YYYY-MM-DD), untuk uji manual}';

    protected $description = 'Kirim pengingat pengembalian: H-1, hari jatuh tempo, dan setiap hari selama terlambat';

    public function handle(PeminjamanService $service): int
    {
        $hariIni = $this->option('tanggal') ? Carbon::parse($this->option('tanggal')) : today();

        $jumlah = $service->kirimPengingat($hariIni);

        $this->info("Tanggal acuan {$hariIni->toDateString()}: {$jumlah} pengingat dikirim (H-1, hari H, dan terlambat).");

        return self::SUCCESS;
    }
}
