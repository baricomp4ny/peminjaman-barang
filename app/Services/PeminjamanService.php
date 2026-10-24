<?php

namespace App\Services;

use App\Enums\KondisiUnit;
use App\Enums\StatusPeminjaman;
use App\Enums\StatusUnit;
use App\Exceptions\PeminjamanException;
use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\Pengaturan;
use App\Models\UnitBarang;
use App\Models\User;
use App\Notifications\AjuanBaru;
use App\Notifications\AjuanDisetujui;
use App\Notifications\AjuanDitolak;
use App\Notifications\AjuanKedaluwarsa;
use App\Notifications\AjuanTerkirim;
use App\Notifications\PengembalianDicatat;
use App\Notifications\PengingatJatuhTempo;
use App\Notifications\PengingatPengembalian;
use App\Notifications\PengingatTerlambat;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Semua perubahan status peminjaman lewat service ini, masing-masing di dalam DB::transaction.
 * Notifikasi dikirim SETELAH transaksi selesai, jadi tidak terkirim bila transaksi gagal.
 */
class PeminjamanService
{
    public function __construct(
        private KetersediaanService $ketersediaan,
        private KeterlambatanService $keterlambatan,
        private KodePeminjamanService $kode,
        private KondisiUnitService $kondisiUnit,
    ) {}

    // ------------------------------------------------------------------
    // 1. Ajuan oleh peminjam
    // ------------------------------------------------------------------

    /**
     * @param  array{tujuan:string, tanggal_pinjam:string, tanggal_rencana_kembali:string, items:array<int, array{barang_id:int, jumlah:int}>}  $data
     */
    public function ajukan(User $peminjam, array $data): Peminjaman
    {
        // Aturan dicek lagi di sini (selain di Form Request) agar service aman dipanggil dari mana pun
        if ($this->punyaPinjamanTerlambat($peminjam)) {
            throw new PeminjamanException('Anda masih memiliki pinjaman yang terlambat. Kembalikan dulu sebelum mengajukan lagi.');
        }
        // Penangguhan setelah mengembalikan terlambat
        if ($pesan = $this->keterlambatan->pesanPenangguhan($peminjam)) {
            throw new PeminjamanException($pesan);
        }

        $peminjaman = DB::transaction(function () use ($peminjam, $data) {
            $peminjaman = Peminjaman::create([
                'kode_peminjaman' => $this->kode->berikutnya(),
                'user_id' => $peminjam->id,
                'tujuan' => $data['tujuan'],
                'surat_peminjaman' => $data['surat_peminjaman'] ?? null,
                'tanggal_pinjam' => $data['tanggal_pinjam'],
                'tanggal_rencana_kembali' => $data['tanggal_rencana_kembali'],
                'status' => StatusPeminjaman::Menunggu,
            ]);

            foreach ($data['items'] as $item) {
                $peminjaman->detail()->create([
                    'barang_id' => $item['barang_id'],
                    'jumlah' => $item['jumlah'],
                ]);
            }

            return $peminjaman;
        });

        Notification::send(
            User::admin()->aktif()->get(),
            new AjuanBaru($peminjaman)
        );
        // Peminjam tidak login → bukti ajuan & kode dikirim ke email
        $peminjam->notify(new AjuanTerkirim($peminjaman));

        return $peminjaman;
    }

    public function punyaPinjamanTerlambat(User $peminjam): bool
    {
        return $peminjam->peminjaman()->terlambat()->exists();
    }

    // ------------------------------------------------------------------
    // 2. Verifikasi admin
    // ------------------------------------------------------------------

    public function setujui(Peminjaman $peminjaman, User $admin): Peminjaman
    {
        DB::transaction(function () use ($peminjaman, $admin) {
            $p = $this->kunci($peminjaman, [StatusPeminjaman::Menunggu]);
            $permintaan = $p->detail->pluck('jumlah', 'barang_id')->all();

            // Kunci baris barang: dua admin yang menyetujui barang sama harus antre
            Barang::whereIn('id', array_keys($permintaan))->lockForUpdate()->get();

            // Cek ULANG ketersediaan di dalam transaksi (bukan hanya stok hari ini)
            $kurang = $this->ketersediaan->kekurangan($permintaan, $p->tanggal_pinjam, $p->tanggal_rencana_kembali);
            if ($kurang !== []) {
                $nama = Barang::whereIn('id', array_keys($kurang))->pluck('nama', 'id');
                $pesan = collect($kurang)->map(fn ($sisa, $id) => "{$nama[$id]} (diminta {$permintaan[$id]}, sisa {$sisa})")->join(', ');
                throw new PeminjamanException("Stok tidak cukup pada rentang tanggal tersebut: {$pesan}.");
            }

            $p->update([
                'status' => StatusPeminjaman::Disetujui,
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now(),
                'alasan_penolakan' => null,
            ]);
        });

        $peminjaman->refresh();
        $peminjaman->peminjam->notify(new AjuanDisetujui($peminjaman));

        return $peminjaman;
    }

    public function tolak(Peminjaman $peminjaman, User $admin, string $alasan): Peminjaman
    {
        if (trim($alasan) === '') {
            throw new PeminjamanException('Alasan penolakan wajib diisi.');
        }

        DB::transaction(function () use ($peminjaman, $admin, $alasan) {
            $this->kunci($peminjaman, [StatusPeminjaman::Menunggu])->update([
                'status' => StatusPeminjaman::Ditolak,
                'alasan_penolakan' => trim($alasan),
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_pada' => now(),
            ]);
        });

        $peminjaman->refresh();
        $peminjaman->peminjam->notify(new AjuanDitolak($peminjaman));

        return $peminjaman;
    }

    // ------------------------------------------------------------------
    // 3. Pembatalan oleh peminjam (sebelum barang diambil)
    // ------------------------------------------------------------------

    public function batalkan(Peminjaman $peminjaman, User $peminjam): Peminjaman
    {
        if ($peminjaman->user_id !== $peminjam->id) {
            throw new PeminjamanException('Anda hanya bisa membatalkan ajuan milik sendiri.');
        }

        DB::transaction(function () use ($peminjaman) {
            $this->kunci($peminjaman, [StatusPeminjaman::Menunggu, StatusPeminjaman::Disetujui])
                ->update(['status' => StatusPeminjaman::Dibatalkan]);
        });

        return $peminjaman->refresh();
    }

    // ------------------------------------------------------------------
    // 4. Serah-terima (Sprint 3: form; Sprint 4: hasil scan QR memakai fungsi yang sama)
    // ------------------------------------------------------------------

    /**
     * Cek satu unit untuk diserahkan pada peminjaman ini.
     * Dipakai juga oleh scan QR (Sprint 4) untuk memvalidasi tiap scan.
     *
     * @param  array<string>  $unitSudahDipilih  id unit yang sudah dipilih/discan sebelumnya
     */
    public function cekUnitUntukSerah(Peminjaman $peminjaman, UnitBarang $unit, array $unitSudahDipilih = []): void
    {
        if (in_array($unit->id, $unitSudahDipilih, true)) {
            throw new PeminjamanException("Unit {$unit->kode_unit} sudah dipilih.");
        }

        $detail = $peminjaman->detail->firstWhere('barang_id', $unit->barang_id);
        if (! $detail) {
            throw new PeminjamanException("Unit {$unit->kode_unit} ({$unit->barang->nama}) tidak termasuk dalam ajuan ini.");
        }

        if (! $unit->bisaDipinjam()) {
            throw new PeminjamanException("Unit {$unit->kode_unit} tidak bisa dipinjam (kondisi {$unit->kondisi->value}, status {$unit->status->value}).");
        }

        $sudahJenisIni = UnitBarang::whereIn('id', $unitSudahDipilih)->where('barang_id', $unit->barang_id)->count();
        if ($sudahJenisIni >= $detail->jumlah) {
            throw new PeminjamanException("Jumlah {$unit->barang->nama} sudah cukup ({$detail->jumlah} unit).");
        }
    }

    /** Pastikan hari ini masih dalam jendela pengambilan. */
    public function cekJendelaSerah(Peminjaman $peminjaman, ?CarbonInterface $hariIni = null): void
    {
        $hariIni = Carbon::parse($hariIni ?? today())->startOfDay();

        if ($hariIni->lt($peminjaman->tanggal_pinjam)) {
            throw new PeminjamanException('Barang belum bisa diambil sebelum tanggal pinjam ('.$peminjaman->tanggal_pinjam->translatedFormat('d F Y').').');
        }

        if ($hariIni->gt($this->batasAmbil($peminjaman))) {
            throw new PeminjamanException('Batas waktu pengambilan sudah lewat. Ajuan ini akan kedaluwarsa.');
        }
    }

    /** Tanggal terakhir barang boleh diambil: min(tanggal pinjam + batas ambil, rencana kembali). */
    public function batasAmbil(Peminjaman $peminjaman): Carbon
    {
        $batas = Carbon::parse($peminjaman->tanggal_pinjam)->addDays(Pengaturan::angka(Pengaturan::BATAS_AMBIL_HARI));

        return $batas->min(Carbon::parse($peminjaman->tanggal_rencana_kembali));
    }

    /**
     * Serahkan unit-unit (berdasarkan kode unit). Jumlah per jenis harus PAS dengan ajuan.
     *
     * @param  array<string>  $kodeUnit
     */
    public function serahkan(Peminjaman $peminjaman, User $admin, array $kodeUnit): Peminjaman
    {
        DB::transaction(function () use ($peminjaman, $kodeUnit) {
            $p = $this->kunci($peminjaman, [StatusPeminjaman::Disetujui]);
            $this->cekJendelaSerah($p);

            $kodeUnit = array_values(array_unique(array_map(fn ($k) => strtoupper(trim($k)), $kodeUnit)));
            $unit = UnitBarang::with('barang')->whereIn('kode_unit', $kodeUnit)->lockForUpdate()->get()->keyBy('kode_unit');

            $tidakDikenal = array_diff($kodeUnit, $unit->keys()->all());
            if ($tidakDikenal !== []) {
                throw new PeminjamanException('Kode unit tidak dikenal: '.implode(', ', $tidakDikenal).'.');
            }

            // Validasi tiap unit: jenis sesuai ajuan, tersedia, tidak melebihi jumlah
            $dipilih = [];
            foreach ($kodeUnit as $kode) {
                $this->cekUnitUntukSerah($p, $unit[$kode], $dipilih);
                $dipilih[] = $unit[$kode]->id;
            }

            // Harus lengkap sebelum status berubah menjadi dipinjam
            $perJenis = $unit->countBy('barang_id');
            foreach ($p->detail as $d) {
                $ada = $perJenis[$d->barang_id] ?? 0;
                if ($ada !== $d->jumlah) {
                    throw new PeminjamanException("Unit {$d->barang->nama} belum lengkap: {$ada} dari {$d->jumlah}.");
                }
            }

            $waktu = now();
            foreach ($unit as $u) {
                PeminjamanUnit::create([
                    'peminjaman_id' => $p->id,
                    'unit_barang_id' => $u->id,
                ]);
                $u->update(['status' => StatusUnit::Dipinjam]);
            }

            $p->update(['status' => StatusPeminjaman::Dipinjam, 'diserahkan_pada' => $waktu]);
        });

        return $peminjaman->refresh();
    }

    // ------------------------------------------------------------------
    // 5. Pengembalian (boleh sebagian)
    // ------------------------------------------------------------------

    /**
     * @param  array<int, array{kondisi:string, catatan?:?string}>  $unit  unit_barang_id => data
     * @return Collection<int, PeminjamanUnit> baris yang baru dikembalikan
     */
    public function kembalikan(Peminjaman $peminjaman, User $admin, array $unit, ?CarbonInterface $waktuKembali = null): Collection
    {
        $waktuKembali = $waktuKembali ? Carbon::parse($waktuKembali) : now();

        if ($waktuKembali->isFuture() && ! $waktuKembali->isToday()) {
            throw new PeminjamanException('Tanggal kembali tidak boleh di masa depan.');
        }

        if ($unit === []) {
            throw new PeminjamanException('Pilih minimal satu unit yang dikembalikan.');
        }

        $dikembalikan = DB::transaction(function () use ($peminjaman, $admin, $unit, $waktuKembali) {
            $p = $this->kunci($peminjaman, [StatusPeminjaman::Dipinjam]);

            if ($waktuKembali->copy()->startOfDay()->lt(Carbon::parse($p->diserahkan_pada)->startOfDay())) {
                throw new PeminjamanException('Tanggal kembali tidak boleh sebelum tanggal serah-terima.');
            }

            $baris = PeminjamanUnit::with('unitBarang')
                ->where('peminjaman_id', $p->id)
                ->whereIn('unit_barang_id', array_keys($unit))
                ->lockForUpdate()
                ->get()
                ->keyBy('unit_barang_id');

            $hasil = collect();

            foreach ($unit as $unitId => $data) {
                $row = $baris[$unitId] ?? null;
                if (! $row) {
                    throw new PeminjamanException('Ada unit yang bukan bagian dari peminjaman ini.');
                }
                if ($row->dikembalikan_pada) {
                    throw new PeminjamanException("Unit {$row->unitBarang->kode_unit} sudah dikembalikan sebelumnya.");
                }

                $row->update([
                    'dikembalikan_pada' => $waktuKembali,
                    'kondisi_kembali' => $data['kondisi'],
                    'hari_terlambat' => $this->keterlambatan->hariTerlambat($p->tanggal_rencana_kembali, $waktuKembali),
                    'catatan' => $data['catatan'] ?? null,
                ]);

                // Kondisi saat kembali menentukan status unit berikutnya (+ dicatat di riwayat kondisi)
                $kondisi = KondisiUnit::from($data['kondisi']);
                $this->kondisiUnit->catat($row->unitBarang, $kondisi, $kondisi->statusUnit(), 'pengembalian', $admin, $data['catatan'] ?? null, $p);

                $hasil->push($row);
            }

            // Semua unit sudah kembali → transaksi selesai
            if (! PeminjamanUnit::where('peminjaman_id', $p->id)->whereNull('dikembalikan_pada')->exists()) {
                $p->update(['status' => StatusPeminjaman::Selesai]);
            }

            return $hasil;
        });

        $peminjaman->refresh();
        $peminjaman->peminjam->notify(new PengembalianDicatat($peminjaman, $dikembalikan));

        return $dikembalikan;
    }

    // ------------------------------------------------------------------
    // 6. Proses terjadwal
    // ------------------------------------------------------------------

    /** Ajuan disetujui yang tidak diambil sampai lewat batas → kedaluwarsa. Mengembalikan jumlah yang diproses. */
    public function prosesKedaluwarsa(?CarbonInterface $hariIni = null): int
    {
        $hariIni = Carbon::parse($hariIni ?? today())->startOfDay();
        $jumlah = 0;

        Peminjaman::with(['peminjam', 'detail'])
            ->where('status', StatusPeminjaman::Disetujui)
            ->orderBy('id')
            ->get()
            ->filter(fn (Peminjaman $p) => $hariIni->gt($this->batasAmbil($p)))
            ->each(function (Peminjaman $p) use (&$jumlah) {
                $berubah = DB::transaction(function () use ($p) {
                    $kunci = Peminjaman::whereKey($p->id)->lockForUpdate()->first();
                    if ($kunci->status !== StatusPeminjaman::Disetujui) {
                        return false; // sudah diambil/dibatalkan di sela proses
                    }
                    $kunci->update(['status' => StatusPeminjaman::Kedaluwarsa]);

                    return true;
                });

                if ($berubah) {
                    $jumlah++;
                    $p->peminjam->notify(new AjuanKedaluwarsa($p->refresh()));
                }
            });

        return $jumlah;
    }

    /**
     * Kirim pengingat pengembalian. Aman dijalankan berulang (tidak dobel):
     *  - H-1      : jatuh tempo besok (sekali per peminjaman)
     *  - Hari H   : jatuh tempo hari ini (sekali per peminjaman)
     *  - Terlambat: setiap hari setelah jatuh tempo (sekali per tanggal)
     *
     * @return int jumlah email yang dikirim
     */
    public function kirimPengingat(?CarbonInterface $hariIni = null): int
    {
        $hariIni = Carbon::parse($hariIni ?? today())->startOfDay();
        $jumlah = 0;

        Peminjaman::with('peminjam')
            ->where('status', StatusPeminjaman::Dipinjam)
            ->whereDate('tanggal_rencana_kembali', '<=', $hariIni->copy()->addDay()->toDateString())
            ->whereHas('peminjamanUnit', fn ($q) => $q->whereNull('dikembalikan_pada'))
            ->get()
            ->each(function (Peminjaman $p) use ($hariIni, &$jumlah) {
                $selisih = (int) $hariIni->diffInDays($p->tanggal_rencana_kembali->copy()->startOfDay(), false);
                $riwayat = $p->peminjam->notifications()->where('data->peminjaman_id', $p->id);

                $notifikasi = match (true) {
                    $selisih === 1 => (clone $riwayat)->where('type', PengingatPengembalian::class)->exists() ? null : new PengingatPengembalian($p),
                    $selisih === 0 => (clone $riwayat)->where('type', PengingatJatuhTempo::class)->exists() ? null : new PengingatJatuhTempo($p),
                    default => (clone $riwayat)->where('type', PengingatTerlambat::class)->where('data->tanggal', $hariIni->toDateString())->exists()
                        ? null : new PengingatTerlambat($p, $hariIni, -$selisih),
                };

                if ($notifikasi) {
                    $p->peminjam->notify($notifikasi);
                    $jumlah++;
                }
            });

        return $jumlah;
    }

    // ------------------------------------------------------------------

    /** Kunci baris peminjaman dan pastikan statusnya sesuai sebelum diubah. */
    private function kunci(Peminjaman $peminjaman, array $statusBoleh): Peminjaman
    {
        $p = Peminjaman::with(['detail.barang'])->whereKey($peminjaman->id)->lockForUpdate()->firstOrFail();

        if (! in_array($p->status, $statusBoleh, true)) {
            throw new PeminjamanException("Aksi tidak bisa dilakukan: status ajuan sekarang \"{$p->status->value}\".");
        }

        return $p;
    }
}
