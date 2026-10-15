<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('kode_peminjaman', 30)->unique(); // contoh PJM-20261015-0001
            $table->foreignUlid('user_id')->constrained('users')->restrictOnDelete();
            $table->text('tujuan');
            $table->date('tanggal_pinjam');
            $table->date('tanggal_rencana_kembali');
            $table->enum('status', [
                'menunggu', 'disetujui', 'ditolak', 'dipinjam', 'selesai', 'dibatalkan', 'kedaluwarsa',
            ])->default('menunggu');
            $table->text('alasan_penolakan')->nullable();
            $table->foreignUlid('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('diverifikasi_pada')->nullable();
            // Waktu serah-terima (berlaku untuk semua unit; tidak diulang di peminjaman_unit)
            $table->dateTime('diserahkan_pada')->nullable();
            $table->timestamps();

            // Dipakai saat cek ketersediaan (status + rentang tanggal)
            $table->index(['status', 'tanggal_pinjam', 'tanggal_rencana_kembali']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
