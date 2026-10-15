<?php

namespace Tests\Concerns;

use App\Enums\StatusPeminjaman;
use App\Enums\StatusUnit;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Pembantu untuk menyiapkan data barang/unit/peminjaman di tes. */
trait DataPeminjaman
{
    private int $urutKode = 0;

    protected function buatBarang(string $kode, int $jumlahUnit = 3, ?string $nama = null): Barang
    {
        $kategori = Kategori::firstOrCreate(['nama' => 'Umum']);
        $lokasi = Lokasi::firstOrCreate(['nama' => 'Gudang']);

        $barang = Barang::create(['kategori_id' => $kategori->id, 'kode' => $kode, 'nama' => $nama ?? 'Barang '.$kode]);

        for ($i = 1; $i <= $jumlahUnit; $i++) {
            UnitBarang::create([
                'barang_id' => $barang->id,
                'lokasi_id' => $lokasi->id,
                'kode_unit' => sprintf('%s-%03d', $kode, $i),
            ]);
        }

        return $barang;
    }

    /**
     * @param  array<string, int>  $barang  barang_id => jumlah
     */
    protected function buatPeminjaman(User $user, StatusPeminjaman|string $status, $mulai, $selesai, array $barang, array $extra = []): Peminjaman
    {
        $p = Peminjaman::create(array_merge([
            'kode_peminjaman' => 'PJM-TES-'.sprintf('%04d', ++$this->urutKode),
            'user_id' => $user->id,
            'tujuan' => 'Keperluan pengujian',
            'tanggal_pinjam' => Carbon::parse($mulai)->toDateString(),
            'tanggal_rencana_kembali' => Carbon::parse($selesai)->toDateString(),
            'status' => $status,
        ], $extra));

        foreach ($barang as $barangId => $jumlah) {
            $p->detail()->create(['barang_id' => $barangId, 'jumlah' => $jumlah]);
        }

        return $p;
    }

    /** Tandai unit-unit sebagai sedang dipinjam oleh peminjaman ini. */
    protected function serahkanUnit(Peminjaman $p, array $kodeUnit, $waktu = null): void
    {
        $waktu = $waktu ? Carbon::parse($waktu) : Carbon::parse($p->tanggal_pinjam)->setTime(9, 0);

        foreach ($kodeUnit as $kode) {
            $unit = UnitBarang::where('kode_unit', $kode)->firstOrFail();
            PeminjamanUnit::create(['peminjaman_id' => $p->id, 'unit_barang_id' => $unit->id]);
            $unit->update(['status' => StatusUnit::Dipinjam]);
        }

        $p->update(['status' => StatusPeminjaman::Dipinjam, 'diserahkan_pada' => $waktu]);
    }
}
