<?php

namespace Database\Seeders;

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
        $admin = User::where('role', User::ROLE_ADMIN)->first();
        [$andi, $budi, $citra, $dewi, $eko] = User::peminjam()->orderBy('id')->get()->all();
        $hariIni = today();

        // 1. Menunggu verifikasi
        $this->buat($andi, 'Presentasi tugas akhir di ruang seminar', $hariIni->copy()->addDays(2), $hariIni->copy()->addDays(4),
            Peminjaman::STATUS_MENUNGGU, ['Proyektor' => 1, 'Layar Proyektor' => 1]);

        // 2. Disetujui, belum diambil
        $this->buat($budi, 'Dokumentasi kegiatan himpunan', $hariIni, $hariIni->copy()->addDays(2),
            Peminjaman::STATUS_DISETUJUI, ['Kamera DSLR' => 1, 'Tripod' => 1], [
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now()->subHours(3),
            ]);

        // 3. Ditolak (alasan wajib)
        $this->buat($citra, 'Acara pribadi', $hariIni->copy()->addDay(), $hariIni->copy()->addDays(10),
            Peminjaman::STATUS_DITOLAK, ['Speaker Portable' => 2], [
                'alasan_penolakan' => 'Lama pinjam melebihi batas maksimal 7 hari dan bukan untuk kegiatan resmi.',
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now()->subDay(),
            ]);

        // 4. Sedang dipinjam, belum jatuh tempo
        $p = $this->buat($dewi, 'Seminar nasional fakultas', $hariIni->copy()->subDay(), $hariIni->copy()->addDays(2),
            Peminjaman::STATUS_DIPINJAM, ['Mikrofon Wireless' => 2], [
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => $hariIni->copy()->subDays(2)->setTime(10, 0),
                'diserahkan_pada' => $hariIni->copy()->subDay()->setTime(8, 30),
            ]);
        $this->serahkan($p, ['MIC-001', 'MIC-002']);

        // 5. Sedang dipinjam dan TERLAMBAT (rencana kembali sudah lewat)
        $p = $this->buat($eko, 'Praktikum jaringan komputer', $hariIni->copy()->subDays(6), $hariIni->copy()->subDays(2),
            Peminjaman::STATUS_DIPINJAM, ['Laptop' => 1], [
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => $hariIni->copy()->subDays(7)->setTime(9, 0),
                'diserahkan_pada' => $hariIni->copy()->subDays(6)->setTime(9, 15),
            ]);
        $this->serahkan($p, ['LPT-001']);

        // 6. Selesai: satu unit kembali terlambat 1 hari (denda), satu unit kembali rusak
        $mulai = $hariIni->copy()->subDays(14);
        $rencana = $hariIni->copy()->subDays(11);
        $p = $this->buat($andi, 'Kuliah umum dosen tamu', $mulai, $rencana,
            Peminjaman::STATUS_SELESAI, ['Proyektor' => 1, 'Kabel Roll' => 1], [
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => $mulai->copy()->subDay()->setTime(13, 0),
                'diserahkan_pada' => $mulai->copy()->setTime(8, 0),
            ]);
        $this->serahkan($p, ['PRJ-004', 'KBL-003']);
        $this->kembalikan($p, 'PRJ-004', $rencana->copy()->addDay()->setTime(10, 0), UnitBarang::KONDISI_BAIK, 1, 1000);
        $this->kembalikan($p, 'KBL-003', $rencana->copy()->setTime(15, 0), UnitBarang::KONDISI_RUSAK, 0, 0, 'Kabel terkelupas di ujung colokan.');

        // 7. Dibatalkan peminjam sebelum diambil
        $this->buat($budi, 'Rapat koordinasi panitia', $hariIni->copy()->addDays(3), $hariIni->copy()->addDays(3),
            Peminjaman::STATUS_DIBATALKAN, ['Speaker Portable' => 1]);

        // 8. Kedaluwarsa: disetujui tetapi tidak diambil
        $this->buat($citra, 'Lomba fotografi', $hariIni->copy()->subDays(5), $hariIni->copy()->subDays(3),
            Peminjaman::STATUS_KEDALUWARSA, ['Kamera DSLR' => 1], [
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => $hariIni->copy()->subDays(6)->setTime(11, 0),
            ]);
    }

    /** Buat header peminjaman + detail jenis barang. */
    private function buat(User $user, string $tujuan, Carbon $mulai, Carbon $selesai, string $status, array $barang, array $extra = []): Peminjaman
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
                'diserahkan_pada' => $peminjaman->diserahkan_pada,
            ]);
            $unit->update(['status' => UnitBarang::STATUS_DIPINJAM]);
        }
    }

    /** Catat pengembalian satu unit dan perbarui status/kondisi unit. */
    private function kembalikan(Peminjaman $peminjaman, string $kode, Carbon $waktu, string $kondisi, int $hariTerlambat, int $denda, ?string $catatan = null): void
    {
        $unit = UnitBarang::where('kode_unit', $kode)->firstOrFail();

        PeminjamanUnit::where('peminjaman_id', $peminjaman->id)->where('unit_barang_id', $unit->id)->update([
            'dikembalikan_pada' => $waktu,
            'kondisi_kembali' => $kondisi,
            'hari_terlambat' => $hariTerlambat,
            'denda' => $denda,
            'catatan' => $catatan,
        ]);

        // Unit rusak masuk perbaikan, unit hilang ditandai hilang, selain itu tersedia lagi
        $unit->update([
            'kondisi' => $kondisi,
            'status' => match ($kondisi) {
                UnitBarang::KONDISI_RUSAK => UnitBarang::STATUS_PERBAIKAN,
                UnitBarang::KONDISI_HILANG => UnitBarang::STATUS_HILANG,
                default => UnitBarang::STATUS_TERSEDIA,
            },
        ]);
    }
}
