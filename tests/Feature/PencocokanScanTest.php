<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use App\Models\User;
use App\Services\ScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

/** Sprint 5 — pencocokan hasil scan (melengkapi Scan\ScanTest), termasuk input scanner USB. */
class PencocokanScanTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $admin;

    private User $peminjam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-10 10:00'));
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
        $this->peminjam = User::factory()->create();
        $this->buatBarang('PRJ', 3, 'Proyektor');
        $this->buatBarang('LPT', 3, 'Laptop');
    }

    private function id(string $kode): string
    {
        return Barang::where('kode', $kode)->value('id');
    }

    private function unitId(string $kode): string
    {
        return UnitBarang::where('kode_unit', $kode)->value('id');
    }

    private function cek(Peminjaman $p, string $kode, array $sudah = [], string $mode = 'serah')
    {
        return $this->actingAs($this->admin)->postJson('/admin/scan/cek-unit', [
            'peminjaman_id' => $p->id, 'kode' => $kode, 'mode' => $mode,
            'sudah' => array_map(fn ($k) => $this->unitId($k), $sudah),
        ]);
    }

    /** @dataProvider kodeKotorDariScanner */
    public function test_normalisasi_kode_dari_scanner_usb(string $mentah): void
    {
        $this->assertSame('PRJ-001', app(ScanService::class)->normalisasi($mentah));

        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-11', [$this->id('PRJ') => 1]);
        $this->cek($p, $mentah)->assertOk()->assertJsonPath('unit.kode_unit', 'PRJ-001');
    }

    public static function kodeKotorDariScanner(): array
    {
        return [
            'huruf kecil' => ['prj-001'],
            'spasi depan-belakang' => ['  PRJ-001  '],
            'akhiran CR LF' => ["PRJ-001\r\n"],
            'awalan Tab' => ["\tPRJ-001"],
            'karakter kontrol di tengah' => ["PRJ\x02-001\x03"],
            'prefix simbologi AIM' => [']Q1PRJ-001'],
        ];
    }

    public function test_kode_terlalu_panjang_dan_kosong_ditolak_dengan_pesan_jelas(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-11', [$this->id('PRJ') => 1]);

        $this->cek($p, str_repeat('A', 150))->assertStatus(422)->assertJsonPath('pesan', fn ($m) => str_contains($m, 'terlalu panjang (150 karakter)'));
        $this->cek($p, "\r\n\t")->assertStatus(422);
        $this->actingAs($this->admin)->postJson('/admin/scan/cari', ['kode' => ']Q1', 'mode' => 'serah'])
            ->assertStatus(422)->assertJsonPath('pesan', 'Kode kosong. Scan QR atau ketik kodenya.');
    }

    public function test_ajuan_multi_barang_dari_scan_pertama_sampai_dipinjam(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-11', [$this->id('PRJ') => 2, $this->id('LPT') => 1]);
        $this->actingAs($this->admin)->postJson('/admin/scan/cari', ['kode' => $p->kode_peminjaman, 'mode' => 'serah'])
            ->assertOk()->assertJsonPath('transaksi.0.id', $p->id)->assertJsonCount(2, 'transaksi.0.detail');

        // Urutan scan nyata, termasuk kesalahan di tengahnya
        $this->cek($p, 'LPT-001')->assertOk();
        $this->cek($p, 'LPT-002', ['LPT-001'])->assertStatus(422)->assertJsonPath('pesan', 'Jumlah Laptop sudah cukup (1 unit).');
        $this->cek($p, 'PRJ-001', ['LPT-001'])->assertOk();
        $this->cek($p, 'XYZ-123', ['LPT-001', 'PRJ-001'])->assertStatus(422)->assertJsonPath('pesan', 'QR tidak dikenal: XYZ-123.');
        $this->cek($p, 'PRJ-001', ['LPT-001', 'PRJ-001'])->assertStatus(422)->assertJsonPath('pesan', 'Unit PRJ-001 sudah dipilih.');
        $this->cek($p, 'PRJ-003', ['LPT-001', 'PRJ-001'])->assertOk();
        $this->cek($p, 'PRJ-002', ['LPT-001', 'PRJ-001', 'PRJ-003'])->assertStatus(422)->assertJsonPath('pesan', 'Jumlah Proyektor sudah cukup (2 unit).');

        $this->post("/admin/peminjaman/{$p->id}/serah", ['kode_unit' => ['LPT-001', 'PRJ-001', 'PRJ-003'], 'dari_scan' => 1])
            ->assertSessionHas('success');

        $this->assertSame('dipinjam', $p->fresh()->status->value);
        $this->assertEqualsCanonicalizing(['LPT-001', 'PRJ-001', 'PRJ-003'], UnitBarang::where('status', 'dipinjam')->pluck('kode_unit')->all());
    }

    public function test_unit_yang_berubah_status_setelah_discan_ditolak_saat_disimpan(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-11', [$this->id('PRJ') => 1]);
        $this->cek($p, 'PRJ-001')->assertOk();

        // Di sela-sela, unit dinyatakan rusak oleh admin lain
        UnitBarang::where('kode_unit', 'PRJ-001')->update(['kondisi' => 'rusak', 'status' => 'perbaikan']);

        $this->post("/admin/peminjaman/{$p->id}/serah", ['kode_unit' => ['PRJ-001'], 'dari_scan' => 1])
            ->assertSessionHas('error', fn ($e) => str_contains($e, 'tidak bisa dipinjam'));

        $this->assertSame('disetujui', $p->fresh()->status->value);
        $this->assertSame(0, PeminjamanUnit::count());
    }

    public function test_transaksi_yang_dibatalkan_di_tengah_scan_ditolak(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-11', [$this->id('PRJ') => 1]);
        $this->cek($p, 'PRJ-001')->assertOk();

        $p->update(['status' => 'dibatalkan']);

        $this->cek($p, 'PRJ-002')->assertStatus(422)->assertJsonPath('pesan', fn ($m) => str_contains($m, 'berstatus "dibatalkan"'));
    }

    public function test_unit_jenis_lain_di_daftar_sudah_tidak_dihitung_untuk_jenis_ini(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-11', [$this->id('PRJ') => 1, $this->id('LPT') => 1]);

        // Sudah ada LPT-001 di daftar; scan PRJ pertama tetap boleh
        $this->cek($p, 'PRJ-002', ['LPT-001'])->assertOk();
    }

    public function test_kartu_qr_peminjam_lama_tidak_lagi_dikenali(): void
    {
        $this->actingAs($this->admin)->postJson('/admin/scan/cari', ['kode' => 'PMJ.1.abcdef0123', 'mode' => 'serah'])
            ->assertStatus(422)->assertJsonPath('pesan', 'QR tidak dikenal: PMJ.1.ABCDEF0123.');
    }

    public function test_kode_peminjaman_tidak_bisa_dipakai_sebagai_unit_di_mode_kembali(): void
    {
        $p = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-09', '2026-10-11', [$this->id('PRJ') => 1]);
        $this->serahkanUnit($p, ['PRJ-001']);

        $this->actingAs($this->admin)->postJson('/admin/scan/cari', ['kode' => $p->kode_peminjaman, 'mode' => 'kembali'])
            ->assertOk()->assertJsonPath('transaksi.0.belum_kembali.0.kode_unit', 'PRJ-001');

        $this->cek($p, $p->kode_peminjaman, [], 'kembali')->assertStatus(422)->assertJsonPath('pesan', fn ($m) => str_contains($m, 'bukan QR unit'));
    }

    public function test_pengembalian_dari_scan_unit_milik_pinjaman_lain(): void
    {
        $a = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-09', '2026-10-11', [$this->id('PRJ') => 1]);
        $this->serahkanUnit($a, ['PRJ-001']);
        $b = $this->buatPeminjaman(User::factory()->create(), 'disetujui', '2026-10-09', '2026-10-11', [$this->id('PRJ') => 1]);
        $this->serahkanUnit($b, ['PRJ-002']);

        // Scan PRJ-002 membuka pinjaman B, bukan A
        $this->actingAs($this->admin)->postJson('/admin/scan/cari', ['kode' => 'prj-002', 'mode' => 'kembali'])
            ->assertOk()->assertJsonPath('transaksi.0.id', $b->id);

        $this->cek($a, 'PRJ-002', [], 'kembali')->assertStatus(422)
            ->assertJsonPath('pesan', "Unit PRJ-002 (Proyektor) bukan bagian dari peminjaman {$a->kode_peminjaman}.");
    }

    public function test_endpoint_scan_menolak_validasi_dasar(): void
    {
        $this->actingAs($this->admin)->postJson('/admin/scan/cek-unit', ['peminjaman_id' => (string) Str::ulid(), 'kode' => 'PRJ-001', 'mode' => 'serah'])
            ->assertStatus(422)->assertJsonValidationErrors('peminjaman_id');
        $this->postJson('/admin/scan/cari', ['kode' => 'PRJ-001', 'mode' => 'pinjam'])
            ->assertStatus(422)->assertJsonValidationErrors('mode');

        $this->actingAs($this->peminjam)->postJson('/admin/scan/cari', ['kode' => 'PRJ-001', 'mode' => 'serah'])->assertForbidden();
    }
}
