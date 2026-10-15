<?php

namespace App\Services;

use App\Exceptions\PeminjamanException;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Logika halaman Scan QR.
 *  - cari()   : mengenali kode pertama (PJM-…, kartu peminjam, atau kode unit) → transaksi
 *  - cekUnit(): memvalidasi setiap unit yang di-scan terhadap transaksi yang sedang dibuka
 * Penyimpanan akhir tetap lewat PeminjamanService::serahkan()/kembalikan().
 */
class ScanService
{
    public const MODE_SERAH = 'serah';
    public const MODE_KEMBALI = 'kembali';

    public const MAKS_PANJANG_KODE = 100;

    public function __construct(
        private QrService $qr,
        private PeminjamanService $peminjaman,
    ) {
    }

    /** Status transaksi yang bisa diproses pada tiap mode. */
    private function statusUntuk(string $mode): string
    {
        return $mode === self::MODE_SERAH ? Peminjaman::STATUS_DISETUJUI : Peminjaman::STATUS_DIPINJAM;
    }

    public function normalisasi(string $kode): string
    {
        // Scanner USB kadang ikut mengirim CR/LF/Tab, karakter kontrol, atau prefix simbologi AIM (mis. "]Q1")
        $kode = preg_replace('/[\x00-\x1F\x7F]/u', '', $kode) ?? '';
        $kode = preg_replace('/^\][A-Za-z]\d/', '', trim($kode)) ?? '';

        return strtoupper(trim($kode));
    }

    /** Normalisasi + tolak kode kosong / terlalu panjang dengan pesan yang jelas. */
    private function kodeBersih(string $kode): string
    {
        $kode = $this->normalisasi($kode);

        if ($kode === '') {
            throw new PeminjamanException('Kode kosong. Scan QR atau ketik kodenya.');
        }
        if (mb_strlen($kode) > self::MAKS_PANJANG_KODE) {
            throw new PeminjamanException('Kode terlalu panjang ('.mb_strlen($kode).' karakter). Pastikan yang di-scan adalah QR dari aplikasi ini.');
        }

        return $kode;
    }

    /**
     * @return Collection<int, Peminjaman>  minimal satu transaksi
     *
     * @throws PeminjamanException
     */
    public function cari(string $kode, string $mode): Collection
    {
        $kode = $this->kodeBersih($kode);
        $status = $this->statusUntuk($mode);

        // 1) Kode peminjaman
        if (str_starts_with($kode, 'PJM-')) {
            $p = Peminjaman::where('kode_peminjaman', $kode)->first();
            if (! $p) {
                throw new PeminjamanException("Kode peminjaman {$kode} tidak ditemukan.");
            }
            if ($p->status !== $status) {
                throw new PeminjamanException("Peminjaman {$kode} berstatus \"{$p->status}\", bukan \"{$status}\".");
            }

            return collect([$p]);
        }

        // 2) Kartu QR peminjam (bertanda tangan)
        if ($this->qr->pakaiFormatKartu($kode)) {
            $userId = $this->qr->bacaKartu($kode);
            $user = $userId ? User::find($userId) : null;
            if (! $user || ! $user->isPeminjam()) {
                throw new PeminjamanException('Kartu QR tidak valid atau sudah tidak berlaku.');
            }
            if (! $user->isAktif()) {
                throw new PeminjamanException("Akun {$user->name} dinonaktifkan.");
            }

            $daftar = $user->peminjaman()->where('status', $status)->orderBy('tanggal_pinjam')->get();
            if ($daftar->isEmpty()) {
                $teks = $mode === self::MODE_SERAH ? 'ajuan disetujui yang siap diambil' : 'barang yang sedang dipinjam';
                throw new PeminjamanException("{$user->name} tidak memiliki {$teks}.");
            }

            return $daftar;
        }

        // 3) Kode unit
        $unit = UnitBarang::where('kode_unit', $kode)->first();
        if (! $unit) {
            throw new PeminjamanException("QR tidak dikenal: {$kode}.");
        }

        if ($mode === self::MODE_SERAH) {
            throw new PeminjamanException("{$kode} adalah kode unit. Scan kartu peminjam atau kode peminjaman terlebih dahulu.");
        }

        // Mode pengembalian: cari pinjaman yang sedang memegang unit ini
        $baris = PeminjamanUnit::with('peminjaman')
            ->where('unit_barang_id', $unit->id)
            ->whereNull('dikembalikan_pada')
            ->whereHas('peminjaman', fn ($q) => $q->where('status', Peminjaman::STATUS_DIPINJAM))
            ->first();
        if (! $baris) {
            throw new PeminjamanException("Unit {$kode} tidak sedang dipinjam.");
        }

        return collect([$baris->peminjaman]);
    }

    /**
     * Validasi satu unit hasil scan.
     *
     * @param  array<int>  $sudah  id unit yang sudah di-scan untuk transaksi ini
     *
     * @throws PeminjamanException
     */
    public function cekUnit(Peminjaman $p, string $kode, string $mode, array $sudah = []): UnitBarang
    {
        $kode = $this->kodeBersih($kode);
        $p->loadMissing('detail.barang');

        if ($p->status !== $this->statusUntuk($mode)) {
            throw new PeminjamanException("Peminjaman {$p->kode_peminjaman} berstatus \"{$p->status}\".");
        }

        if (str_starts_with($kode, 'PJM-') || $this->qr->pakaiFormatKartu($kode)) {
            throw new PeminjamanException('Yang di-scan bukan QR unit barang. Selesaikan transaksi ini dulu atau klik "Ganti transaksi".');
        }

        $unit = UnitBarang::with('barang')->where('kode_unit', $kode)->first();
        if (! $unit) {
            throw new PeminjamanException("QR tidak dikenal: {$kode}.");
        }

        if ($mode === self::MODE_SERAH) {
            // Jenis sesuai ajuan, unit tersedia, tidak dobel, tidak melebihi jumlah ajuan
            $this->peminjaman->cekUnitUntukSerah($p, $unit, array_map('intval', $sudah));

            return $unit;
        }

        if (in_array($unit->id, array_map('intval', $sudah), true)) {
            throw new PeminjamanException("Unit {$unit->kode_unit} sudah di-scan.");
        }

        $baris = PeminjamanUnit::where('peminjaman_id', $p->id)->where('unit_barang_id', $unit->id)->first();
        if (! $baris) {
            throw new PeminjamanException("Unit {$unit->kode_unit} ({$unit->barang->nama}) bukan bagian dari peminjaman {$p->kode_peminjaman}.");
        }
        if ($baris->dikembalikan_pada) {
            throw new PeminjamanException("Unit {$unit->kode_unit} sudah dikembalikan pada ".$baris->dikembalikan_pada->translatedFormat('d M Y H:i').'.');
        }

        return $unit;
    }

    /** Data transaksi untuk ditampilkan di halaman scan (JSON). */
    public function ringkasan(Peminjaman $p, string $mode): array
    {
        $p->loadMissing(['peminjam', 'detail.barang', 'peminjamanUnit.unitBarang.barang']);

        $jendela = null;
        if ($mode === self::MODE_SERAH) {
            try {
                $this->peminjaman->cekJendelaSerah($p);
            } catch (PeminjamanException $e) {
                $jendela = $e->getMessage();
            }
        }

        return [
            'id' => $p->id,
            'kode' => $p->kode_peminjaman,
            'status' => $p->status,
            'peminjam' => $p->peminjam->name,
            'nomor_identitas' => $p->peminjam->nomor_identitas,
            'tanggal' => $p->tanggal_pinjam->translatedFormat('d M Y').' s/d '.$p->tanggal_rencana_kembali->translatedFormat('d M Y'),
            'terlambat' => $p->isTerlambat() ? $p->hariTerlambatBerjalan() : 0,
            'peringatan' => $jendela,
            'detail' => $p->detail->map(fn ($d) => [
                'barang_id' => $d->barang_id,
                'nama' => $d->barang->nama,
                'jumlah' => $d->jumlah,
            ])->values(),
            // Untuk mode pengembalian: unit yang masih di tangan peminjam
            'belum_kembali' => $p->peminjamanUnit->whereNull('dikembalikan_pada')->map(fn ($u) => [
                'unit_id' => $u->unit_barang_id,
                'kode_unit' => $u->unitBarang->kode_unit,
                'nama' => $u->unitBarang->barang->nama,
            ])->values(),
            'url_detail' => route('admin.peminjaman.show', $p),
        ];
    }
}
