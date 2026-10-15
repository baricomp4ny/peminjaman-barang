<?php

namespace Tests\Feature;

use App\Enums\StatusPeminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use App\Models\User;
use App\Services\KetersediaanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/**
 * Sprint 5 — pengujian KetersediaanService (melengkapi Transaksi\KetersediaanTest).
 * Aturan: sisa = unit baik (tersedia/dipinjam) − ajuan disetujui yang bertabrakan
 *         − unit belum kembali dari pinjaman yang bertabrakan.
 */
class KetersediaanServiceTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private KetersediaanService $svc;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-10 10:00'));
        $this->svc = app(KetersediaanService::class);
        $this->user = User::factory()->create();
    }

    public function test_beberapa_ajuan_yang_bertabrakan_dijumlahkan(): void
    {
        $b = $this->buatBarang('PRJ', 5);
        $this->buatPeminjaman($this->user, 'disetujui', '2026-10-11', '2026-10-13', [$b->id => 2]);
        $this->buatPeminjaman($this->user, 'disetujui', '2026-10-13', '2026-10-15', [$b->id => 1]);
        $this->buatPeminjaman($this->user, 'disetujui', '2026-10-16', '2026-10-17', [$b->id => 3]);

        $this->assertSame(2, $this->svc->sisa($b->id, '2026-10-13', '2026-10-13'), 'Tanggal 13: 2 + 1 terpakai');
        $this->assertSame(2, $this->svc->sisa($b->id, '2026-10-11', '2026-10-15'));
        $this->assertSame(0, $this->svc->sisa($b->id, '2026-10-12', '2026-10-16'), '2 + 1 + 3 = 6 > 5, sisa tidak negatif');
        $this->assertSame(2, $this->svc->sisa($b->id, '2026-10-16', '2026-10-20'));
    }

    public function test_banyak_barang_dihitung_dalam_satu_panggilan(): void
    {
        $a = $this->buatBarang('PRJ', 3);
        $b = $this->buatBarang('LPT', 2);
        $c = $this->buatBarang('KMR', 0);
        $this->buatPeminjaman($this->user, 'disetujui', '2026-10-10', '2026-10-11', [$a->id => 1, $b->id => 2]);

        $hasil = $this->svc->hitung([$a->id, $b->id, $c->id, $a->id], '2026-10-10', '2026-10-10');

        $this->assertSame(['kapasitas' => 3, 'terpakai' => 1, 'sisa' => 2], $hasil[$a->id]);
        $this->assertSame(['kapasitas' => 2, 'terpakai' => 2, 'sisa' => 0], $hasil[$b->id]);
        $this->assertSame(['kapasitas' => 0, 'terpakai' => 0, 'sisa' => 0], $hasil[$c->id]);
        $this->assertCount(3, $hasil, 'ID duplikat hanya dihitung sekali');
        $this->assertSame([], $this->svc->hitung([], '2026-10-10', '2026-10-10'));
    }

    public function test_unit_yang_menjadi_rusak_langsung_mengurangi_kapasitas(): void
    {
        $b = $this->buatBarang('PRJ', 3);
        $this->assertSame(3, $this->svc->sisa($b->id, '2026-10-10', '2026-10-10'));

        UnitBarang::where('kode_unit', 'PRJ-001')->update(['kondisi' => 'rusak', 'status' => 'perbaikan']);
        $this->assertSame(2, $this->svc->sisa($b->id, '2026-10-10', '2026-10-10'));

        // Kondisi baik tapi berstatus perbaikan (sedang diservis) juga tidak dihitung
        UnitBarang::where('kode_unit', 'PRJ-002')->update(['status' => 'perbaikan']);
        $this->assertSame(1, $this->svc->sisa($b->id, '2026-10-10', '2026-10-10'));
    }

    public function test_pinjaman_jatuh_tempo_hari_ini_masih_memblokir_hari_ini_tapi_tidak_besok(): void
    {
        $b = $this->buatBarang('PRJ', 1);
        $p = $this->buatPeminjaman($this->user, 'disetujui', '2026-10-08', '2026-10-10', [$b->id => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);

        $this->assertSame(0, $this->svc->sisa($b->id, '2026-10-10', '2026-10-10'));
        $this->assertSame(1, $this->svc->sisa($b->id, '2026-10-11', '2026-10-11'));

        // Lusa: pinjaman jadi terlambat → memblokir hari itu DAN semua tanggal sesudahnya sampai unit kembali
        $this->travelTo(Carbon::parse('2026-10-12 08:00'));
        $this->assertSame(0, $this->svc->sisa($b->id, '2026-10-12', '2026-10-12'));
        $this->assertSame(0, $this->svc->sisa($b->id, '2026-11-01', '2026-11-02'));
    }

    public function test_pinjaman_yang_selesai_tidak_lagi_memblokir(): void
    {
        $b = $this->buatBarang('PRJ', 1);
        $p = $this->buatPeminjaman($this->user, 'disetujui', '2026-10-09', '2026-10-12', [$b->id => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);
        $this->assertSame(0, $this->svc->sisa($b->id, '2026-10-10', '2026-10-11'));

        PeminjamanUnit::where('peminjaman_id', $p->id)->update(['dikembalikan_pada' => now(), 'kondisi_kembali' => 'baik']);
        UnitBarang::where('kode_unit', 'PRJ-001')->update(['status' => 'tersedia']);
        $p->update(['status' => StatusPeminjaman::Selesai]);

        $this->assertSame(1, $this->svc->sisa($b->id, '2026-10-10', '2026-10-11'));
    }

    /**
     * Uji acak: 200 pertanyaan ketersediaan pada skenario acak (seed tetap agar bisa diulang),
     * hasil service (query SQL) dibandingkan dengan perhitungan manual sederhana di PHP.
     */
    public function test_acak_hasil_service_sama_dengan_perhitungan_manual(): void
    {
        mt_srand(20261010);
        $hariIni = Carbon::parse('2026-10-10');
        $barang = [$this->buatBarang('PRJ', 6), $this->buatBarang('LPT', 4)];

        // Beberapa unit rusak/perbaikan/hilang
        UnitBarang::where('kode_unit', 'PRJ-006')->update(['kondisi' => 'rusak', 'status' => 'perbaikan']);
        UnitBarang::where('kode_unit', 'LPT-004')->update(['kondisi' => 'hilang', 'status' => 'hilang']);

        $status = ['menunggu', 'disetujui', 'disetujui', 'dipinjam', 'dipinjam', 'ditolak', 'dibatalkan', 'selesai', 'kedaluwarsa'];
        $rekaman = [];

        for ($i = 0; $i < 25; $i++) {
            $b = $barang[mt_rand(0, 1)];
            $mulai = $hariIni->copy()->addDays(mt_rand(-8, 8));
            $selesai = $mulai->copy()->addDays(mt_rand(0, 5));
            $st = $status[mt_rand(0, count($status) - 1)];

            if ($st === 'dipinjam') {
                // Pinjaman berjalan hanya bila tanggal mulainya sudah lewat dan masih ada unit tersedia
                $mulai = $hariIni->copy()->subDays(mt_rand(0, 6));
                $selesai = $mulai->copy()->addDays(mt_rand(0, 8));
                $bebas = UnitBarang::where('barang_id', $b->id)->where('kondisi', 'baik')->where('status', 'tersedia')->pluck('kode_unit');
                if ($bebas->isEmpty()) {
                    continue;
                }
                $kode = $bebas->shuffle(mt_rand())->take(mt_rand(1, min(2, $bebas->count())))->values()->all();
                $p = $this->buatPeminjaman($this->user, 'disetujui', $mulai, $selesai, [$b->id => count($kode)]);
                $this->serahkanUnit($p, $kode);

                // Kadang sebagian sudah dikembalikan
                $kembali = [];
                if (count($kode) === 2 && mt_rand(0, 1)) {
                    $kembali[] = $kode[0];
                    PeminjamanUnit::where('peminjaman_id', $p->id)
                        ->where('unit_barang_id', UnitBarang::where('kode_unit', $kode[0])->value('id'))
                        ->update(['dikembalikan_pada' => now(), 'kondisi_kembali' => 'baik']);
                    UnitBarang::where('kode_unit', $kode[0])->update(['status' => 'tersedia']);
                }
                $rekaman[] = ['status' => 'dipinjam', 'barang' => $b->id, 'mulai' => $mulai, 'selesai' => $selesai, 'jumlah' => count($kode) - count($kembali)];
            } else {
                $jumlah = mt_rand(1, 3);
                $this->buatPeminjaman($this->user, $st, $mulai, $selesai, [$b->id => $jumlah]);
                $rekaman[] = ['status' => $st, 'barang' => $b->id, 'mulai' => $mulai, 'selesai' => $selesai, 'jumlah' => $jumlah];
            }
        }

        // Perhitungan manual (rumus dari CLAUDE.md)
        $manual = function (string $barangId, Carbon $mulai, Carbon $selesai) use ($rekaman, $hariIni) {
            $kapasitas = UnitBarang::where('barang_id', $barangId)->where('kondisi', 'baik')
                ->whereIn('status', ['tersedia', 'dipinjam'])->count();
            $terpakai = 0;
            foreach ($rekaman as $r) {
                if ($r['barang'] !== $barangId) {
                    continue;
                }
                // Pinjaman terlambat (rencana < hari ini) memblokir semua tanggal ke depan
                $terlambat = $r['status'] === 'dipinjam' && $r['selesai']->lt($hariIni);
                $tabrakan = $r['mulai']->lte($selesai) && ($terlambat || $r['selesai']->gte($mulai));
                if ($tabrakan && in_array($r['status'], ['disetujui', 'dipinjam'], true)) {
                    $terpakai += $r['jumlah'];
                }
            }

            return max(0, $kapasitas - $terpakai);
        };

        for ($q = 0; $q < 200; $q++) {
            $b = $barang[mt_rand(0, 1)];
            $mulai = $hariIni->copy()->addDays(mt_rand(-10, 12));
            $selesai = $mulai->copy()->addDays(mt_rand(0, 7));

            $this->assertSame(
                $manual($b->id, $mulai, $selesai),
                $this->svc->sisa($b->id, $mulai, $selesai),
                "Pertanyaan #{$q}: barang {$b->kode}, {$mulai->toDateString()} s/d {$selesai->toDateString()}"
            );
        }
    }
}
