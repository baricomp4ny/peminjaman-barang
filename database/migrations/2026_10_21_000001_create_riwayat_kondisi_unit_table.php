<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Riwayat perubahan kondisi/status unit: rusak saat pengembalian, diubah admin, perbaikan selesai. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_kondisi_unit', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('unit_barang_id')->constrained('unit_barang')->cascadeOnDelete();
            $table->string('jenis', 30); // pengembalian | ubah_manual | perbaikan_selesai
            $table->string('kondisi_lama', 20);
            $table->string('kondisi_baru', 20);
            $table->string('status_lama', 20);
            $table->string('status_baru', 20);
            $table->text('keterangan')->nullable();
            $table->foreignUlid('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('peminjaman_id')->nullable()->constrained('peminjaman')->nullOnDelete();
            $table->timestamps();

            $table->index(['unit_barang_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kondisi_unit');
    }
};
