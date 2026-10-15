<?php

namespace Tests\Feature\Scan;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\PeminjamanUnit;
use App\Models\UnitBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\DataPeminjaman;
use Tests\TestCase;

class ScanTest extends TestCase
{
    use DataPeminjaman, RefreshDatabase;

    private User $admin;

    private User $peminjam;

    private Peminjaman $ajuan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 10)->setTime(10, 0));
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
        $this->peminjam = User::factory()->create(['name' => 'Rina Kartika']);
        $this->buatBarang('PRJ', 4, 'Proyektor');
        $this->buatBarang('LPT', 2, 'Laptop');
        UnitBarang::where('kode_unit', 'PRJ-004')->update(['kondisi' => 'rusak', 'status' => 'perbaikan']);

        // Ajuan disetujui: 2 proyektor
        $this->ajuan = $this->buatPeminjaman($this->peminjam, 'disetujui', '2026-10-10', '2026-10-12', [$this->idBarang('PRJ') => 2]);
    }

    private function idBarang(string $kode): string
    {
        return Barang::where('kode', $kode)->value('id');
    }

    private function idUnit(string $kode): string
    {
        return UnitBarang::where('kode_unit', $kode)->value('id');
    }

    private function cari(string $kode, string $mode = 'serah')
    {
        return $this->actingAs($this->admin)->postJson('/admin/scan/cari', compact('kode', 'mode'));
    }

    private function cek(Peminjaman $p, string $kode, array $sudah = [], string $mode = 'serah')
    {
        return $this->actingAs($this->admin)->postJson('/admin/scan/cek-unit', [
            'peminjaman_id' => $p->id, 'kode' => $kode, 'mode' => $mode, 'sudah' => array_map(fn ($k) => $this->idUnit($k), $sudah),
        ]);
    }

    // ---------- Langkah 1: membuka transaksi ----------

    public function test_halaman_scan_bisa_dibuka(): void
    {
        $this->actingAs($this->admin)->get('/admin/scan')->assertOk()->assertSee('vendor/html5-qrcode/html5-qrcode.min.js');
        $this->get('/admin/scan?mode=kembali&peminjaman='.$this->ajuan->id)->assertOk()->assertSee($this->ajuan->kode_peminjaman);
        $this->assertFileExists(public_path('vendor/html5-qrcode/html5-qrcode.min.js'));
    }

    public function test_buka_transaksi_dengan_kode_peminjaman(): void
    {
        $this->cari(strtolower($this->ajuan->kode_peminjaman))
            ->assertOk()
            ->assertJsonPath('transaksi.0.kode', $this->ajuan->kode_peminjaman)
            ->assertJsonPath('transaksi.0.peminjam', 'Rina Kartika')
            ->assertJsonPath('transaksi.0.detail.0.jumlah', 2);
    }

    public function test_qr_tidak_dikenal_saat_membuka_transaksi(): void
    {
        $this->cari('ABC-123')->assertStatus(422)->assertJsonPath('pesan', 'QR tidak dikenal: ABC-123.');
        $this->cari('PMJ.1.0000000000')->assertStatus(422)->assertJsonPath('pesan', 'QR tidak dikenal: PMJ.1.0000000000.');
        $this->cari('PJM-20990101-9999')->assertStatus(422)->assertJsonPath('pesan', fn ($p) => str_contains($p, 'tidak ditemukan'));
        $this->cari('')->assertStatus(422)->assertJsonValidationErrors('kode');
    }

    public function test_status_transaksi_harus_sesuai_mode(): void
    {
        $this->cari($this->ajuan->kode_peminjaman, 'kembali')->assertStatus(422)
            ->assertJsonPath('pesan', fn ($p) => str_contains($p, 'berstatus "disetujui"'));

        // Di mode serah-terima, kode unit tidak bisa dipakai membuka transaksi
        $this->cari('PRJ-001', 'serah')->assertStatus(422)->assertJsonPath('pesan', fn ($p) => str_contains($p, 'Scan QR kode peminjaman'));
    }

    // ---------- Langkah 2: serah-terima ----------

    public function test_scan_unit_yang_sesuai_diterima(): void
    {
        $this->cek($this->ajuan, 'prj-001')->assertOk()->assertJsonPath('unit.kode_unit', 'PRJ-001')->assertJsonPath('unit.nama', 'Proyektor');
        $this->cek($this->ajuan, 'PRJ-002', ['PRJ-001'])->assertOk();
    }

    public function test_scan_qr_tidak_dikenal(): void
    {
        $this->cek($this->ajuan, 'XYZ-999')->assertStatus(422)->assertJsonPath('pesan', 'QR tidak dikenal: XYZ-999.');
    }

    public function test_scan_unit_yang_jenisnya_tidak_sesuai_ajuan(): void
    {
        $this->cek($this->ajuan, 'LPT-001')->assertStatus(422)
            ->assertJsonPath('pesan', 'Unit LPT-001 (Laptop) tidak termasuk dalam ajuan ini.');
    }

    public function test_scan_melebihi_jumlah_ajuan(): void
    {
        $this->cek($this->ajuan, 'PRJ-003', ['PRJ-001', 'PRJ-002'])->assertStatus(422)
            ->assertJsonPath('pesan', 'Jumlah Proyektor sudah cukup (2 unit).');
    }

    public function test_scan_ganda_unit_yang_sama(): void
    {
        $this->cek($this->ajuan, 'PRJ-001', ['PRJ-001'])->assertStatus(422)->assertJsonPath('pesan', 'Unit PRJ-001 sudah dipilih.');
    }

    public function test_scan_unit_rusak_atau_sedang_dipinjam(): void
    {
        $this->cek($this->ajuan, 'PRJ-004')->assertStatus(422)->assertJsonPath('pesan', fn ($p) => str_contains($p, 'tidak bisa dipinjam'));

        $lain = $this->buatPeminjaman(User::factory()->create(), 'disetujui', '2026-10-09', '2026-10-11', [$this->idBarang('PRJ') => 1]);
        $this->serahkanUnit($lain, ['PRJ-003']);
        $this->cek($this->ajuan, 'PRJ-003')->assertStatus(422)->assertJsonPath('pesan', fn ($p) => str_contains($p, 'status dipinjam'));
    }

    public function test_scan_kode_peminjaman_saat_menunggu_unit_ditolak(): void
    {
        $this->cek($this->ajuan, $this->ajuan->kode_peminjaman)->assertStatus(422)->assertJsonPath('pesan', fn ($p) => str_contains($p, 'bukan QR unit'));
    }

    public function test_alur_scan_serah_terima_sampai_dipinjam(): void
    {
        $this->cek($this->ajuan, 'PRJ-001')->assertOk();
        $this->cek($this->ajuan, 'PRJ-002', ['PRJ-001'])->assertOk();

        // Tombol "Selesaikan" mengirim form ke endpoint serah-terima yang sama dengan form manual
        $this->post("/admin/peminjaman/{$this->ajuan->id}/serah", ['kode_unit' => ['PRJ-001', 'PRJ-002'], 'dari_scan' => 1])
            ->assertRedirect('/admin/scan?mode=serah')
            ->assertSessionHas('success');

        $this->assertSame('dipinjam', $this->ajuan->fresh()->status->value);
        $this->assertSame(2, UnitBarang::where('status', 'dipinjam')->count());
    }

    public function test_simpan_serah_terima_yang_belum_lengkap_tetap_ditolak_server(): void
    {
        $this->actingAs($this->admin)->post("/admin/peminjaman/{$this->ajuan->id}/serah", ['kode_unit' => ['PRJ-001'], 'dari_scan' => 1])
            ->assertSessionHas('error', fn ($e) => str_contains($e, 'belum lengkap'));

        $this->assertSame('disetujui', $this->ajuan->fresh()->status->value);
    }

    // ---------- Pengembalian ----------

    private function pinjamanBerjalan(): Peminjaman
    {
        $this->serahkanUnit($this->ajuan, ['PRJ-001', 'PRJ-002']);

        return $this->ajuan->fresh();
    }

    public function test_buka_pengembalian_dengan_scan_unit(): void
    {
        $p = $this->pinjamanBerjalan();

        $this->cari('PRJ-002', 'kembali')->assertOk()
            ->assertJsonPath('transaksi.0.id', $p->id)
            ->assertJsonCount(2, 'transaksi.0.belum_kembali');

        $this->cari('PRJ-003', 'kembali')->assertStatus(422)->assertJsonPath('pesan', 'Unit PRJ-003 tidak sedang dipinjam.');
    }

    public function test_scan_pengembalian_unit_bukan_milik_pinjaman(): void
    {
        $p = $this->pinjamanBerjalan();

        $this->cek($p, 'PRJ-003', [], 'kembali')->assertStatus(422)
            ->assertJsonPath('pesan', fn ($m) => str_contains($m, 'bukan bagian dari peminjaman'));
        $this->cek($p, 'LPT-001', [], 'kembali')->assertStatus(422);
        $this->cek($p, 'ZZZ-000', [], 'kembali')->assertStatus(422)->assertJsonPath('pesan', 'QR tidak dikenal: ZZZ-000.');
        $this->cek($p, 'PRJ-001', ['PRJ-001'], 'kembali')->assertStatus(422)->assertJsonPath('pesan', 'Unit PRJ-001 sudah di-scan.');
        $this->cek($p, 'PRJ-001', [], 'kembali')->assertOk();
    }

    public function test_alur_scan_pengembalian_sebagian(): void
    {
        $p = $this->pinjamanBerjalan();
        $id1 = $this->idUnit('PRJ-001');

        $this->actingAs($this->admin)->post("/admin/pengembalian/{$p->id}", [
            'tanggal_kembali' => '2026-10-10', 'dari_scan' => 1,
            'unit' => [$id1 => ['pilih' => 1, 'kondisi' => 'rusak', 'catatan' => 'Lensa retak']],
        ])->assertRedirect('/admin/scan?mode=kembali')->assertSessionHas('success');

        $this->assertSame('dipinjam', $p->fresh()->status->value);
        $this->assertSame('perbaikan', UnitBarang::find($id1)->status->value);

        // Unit yang sudah kembali tidak bisa di-scan lagi
        $this->cek($p, 'PRJ-001', [], 'kembali')->assertStatus(422)->assertJsonPath('pesan', fn ($m) => str_contains($m, 'sudah dikembalikan'));
        $this->assertSame(1, PeminjamanUnit::whereNull('dikembalikan_pada')->count());
    }
}
