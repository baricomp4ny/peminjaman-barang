<?php

namespace Database\Seeders;

use App\Enums\KondisiUnit;
use App\Enums\StatusPeminjaman;
use App\Enums\StatusUnit;
use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Contoh transaksi di ketujuh status. Status unit disesuaikan agar konsisten
 * dengan transaksinya (unit yang sedang dipinjam berstatus "dipinjam").
 */
class PeminjamanSeeder extends Seeder
{
    private int $urut = 0;

    public function run(): void
    {
        $admin = User::admin()->first();
        // Ambil berdasarkan email agar tidak tertukar dengan user lain yang mungkin sudah ada
        [$andi, $budi, $citra, $dewi, $eko] = collect(['andi', 'budi', 'citra', 'dewi', 'eko'])
            ->map(fn ($nama) => User::where('email', "{$nama}@peminjaman.test")->firstOrFail())
            ->all();
        $hariIni = today();

        // 1. Menunggu verifikasi
        $this->buat($andi, 'Pengamatan kalus anggrek untuk tugas akhir', $hariIni->copy()->addDays(2), $hariIni->copy()->addDays(4),
            StatusPeminjaman::Menunggu, ['Mikroskop Stereo' => 1, 'Set Erlenmeyer' => 1]);

        // 2. Disetujui, belum diambil
        $this->buat($budi, 'Pembuatan media MS praktikum Kultur Jaringan Tanaman', $hariIni, $hariIni->copy()->addDays(2),
            StatusPeminjaman::Disetujui, ['Timbangan Analitik' => 1, 'Bunsen Burner' => 1], [
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now()->subHours(3),
            ]);

        // 3. Ditolak (alasan wajib)
        $this->buat($citra, 'Keperluan pribadi di luar laboratorium', $hariIni->copy()->addDay(), $hariIni->copy()->addDays(10),
            StatusPeminjaman::Ditolak, ['Hot Plate Stirrer' => 2], [
                'alasan_penolakan' => 'Lama pinjam melebihi batas maksimal 7 hari dan alat tidak boleh dibawa ke luar laboratorium.',
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now()->subDay(),
            ]);

        // 4. Sedang dipinjam, belum jatuh tempo
        $p = $this->buat($dewi, 'Sterilisasi alat untuk praktikum subkultur pisang', $hariIni->copy()->subDay(), $hariIni->copy()->addDays(2),
            StatusPeminjaman::Dipinjam, ['Set Cawan Petri' => 2], [
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => $hariIni->copy()->subDays(2)->setTime(10, 0),
                'diserahkan_pada' => $hariIni->copy()->subDay()->setTime(8, 30),
            ]);
        $this->serahkan($p, ['CTP-001', 'CTP-002']);

        // 5. Sedang dipinjam dan TERLAMBAT (rencana kembali sudah lewat)
        $p = $this->buat($eko, 'Pengukuran pH media penelitian kentang', $hariIni->copy()->subDays(6), $hariIni->copy()->subDays(2),
            StatusPeminjaman::Dipinjam, ['pH Meter Digital' => 1], [
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => $hariIni->copy()->subDays(7)->setTime(9, 0),
                'diserahkan_pada' => $hariIni->copy()->subDays(6)->setTime(9, 15),
            ]);
        $this->serahkan($p, ['PHM-001']);

        // 6. Selesai: satu unit kembali terlambat 2 hari (Andi jadi masa penangguhan), satu unit kembali rusak
        $mulai = $hariIni->copy()->subDays(6);
        $rencana = $hariIni->copy()->subDays(4);
        $p = $this->buat($andi, 'Inisiasi eksplan krisan untuk penelitian dosen', $mulai, $rencana,
            StatusPeminjaman::Selesai, ['Mikroskop Stereo' => 1, 'Bead Sterilizer' => 1], [
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => $mulai->copy()->subDay()->setTime(13, 0),
                'diserahkan_pada' => $mulai->copy()->setTime(8, 0),
            ]);
        $this->serahkan($p, ['MKR-004', 'STR-003']);
        $this->kembalikan($p, 'MKR-004', $rencana->copy()->addDays(2)->setTime(10, 0), KondisiUnit::Baik, 2);
        $this->kembalikan($p, 'STR-003', $rencana->copy()->setTime(15, 0), KondisiUnit::Rusak, 0, 'Elemen pemanas tidak panas saat dicoba.');

        // 7. Dibatalkan peminjam sebelum diambil
        $this->buat($budi, 'Persiapan praktikum aklimatisasi', $hariIni->copy()->addDays(3), $hariIni->copy()->addDays(3),
            StatusPeminjaman::Dibatalkan, ['Hot Plate Stirrer' => 1]);

        // 8. Kedaluwarsa: disetujui tetapi tidak diambil
        $this->buat($citra, 'Penimbangan zat pengatur tumbuh', $hariIni->copy()->subDays(5), $hariIni->copy()->subDays(3),
            StatusPeminjaman::Kedaluwarsa, ['Timbangan Analitik' => 1], [
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => $hariIni->copy()->subDays(6)->setTime(11, 0),
            ]);
    }

    /** Buat header peminjaman + detail jenis barang. */
    private function buat(User $user, string $tujuan, Carbon $mulai, Carbon $selesai, StatusPeminjaman $status, array $barang, array $extra = []): Peminjaman
    {
        $dibuat = $mulai->copy()->subDays(2)->setTime(9, 0);
        $this->urut++;

        $peminjaman = Peminjaman::create(array_merge([
            'kode_peminjaman' => sprintf('PJM-%s-%04d', $dibuat->format('Ymd'), $this->urut),
            'user_id' => $user->id,
            'tujuan' => $tujuan,
            'tanggal_pinjam' => $mulai->toDateString(),
            'tanggal_rencana_kembali' => $selesai->toDateString(),
            'status' => $status,
        ], $extra));

        foreach ($barang as $nama => $jumlah) {
            $peminjaman->detail()->create([
                'barang_id' => Barang::where('nama', $nama)->value('id'),
                'jumlah' => $jumlah,
            ]);
        }

        return $peminjaman;
    }

    /** Catat serah-terima unit dan ubah status unit menjadi dipinjam. */
    private function serahkan(Peminjaman $peminjaman, array $kodeUnit): void
    {
        foreach ($kodeUnit as $kode) {
            $unit = UnitBarang::where('kode_unit', $kode)->firstOrFail();
            PeminjamanUnit::create([
                'peminjaman_id' => $peminjaman->id,
                'unit_barang_id' => $unit->id,
            ]);
            $unit->update(['status' => StatusUnit::Dipinjam]);
        }
    }

    /** Catat pengembalian satu unit dan perbarui status/kondisi unit. */
    private function kembalikan(Peminjaman $peminjaman, string $kode, Carbon $waktu, KondisiUnit $kondisi, int $hariTerlambat, ?string $catatan = null): void
    {
        $unit = UnitBarang::where('kode_unit', $kode)->firstOrFail();

        PeminjamanUnit::where('peminjaman_id', $peminjaman->id)->where('unit_barang_id', $unit->id)->update([
            'dikembalikan_pada' => $waktu,
            'kondisi_kembali' => $kondisi,
            'hari_terlambat' => $hariTerlambat,
            'catatan' => $catatan,
        ]);

        // Unit rusak masuk perbaikan, unit hilang ditandai hilang, selain itu tersedia lagi
        $unit->update([
            'kondisi' => $kondisi,
            'status' => match ($kondisi) {
                KondisiUnit::Rusak => StatusUnit::Perbaikan,
                KondisiUnit::Hilang => StatusUnit::Hilang,
                default => StatusUnit::Tersedia,
            },
        ]);
    }
}
