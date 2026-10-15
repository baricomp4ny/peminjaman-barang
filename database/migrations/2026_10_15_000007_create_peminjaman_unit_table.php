<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unit fisik yang diserahkan (hasil scan) beserta data pengembaliannya.
        // Waktu serah-terima cukup di peminjaman.diserahkan_pada (tidak diduplikasi di sini).
        Schema::create('peminjaman_unit', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('peminjaman_id')->constrained('peminjaman')->cascadeOnDelete();
            $table->foreignUlid('unit_barang_id')->constrained('unit_barang')->restrictOnDelete();
            $table->dateTime('dikembalikan_pada')->nullable();
            $table->enum('kondisi_kembali', ['baik', 'rusak', 'hilang'])->nullable();
            $table->unsignedInteger('hari_terlambat')->default(0);
            $table->unsignedBigInteger('denda')->default(0); // dalam rupiah, tarif dibekukan saat dicatat
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->unique(['peminjaman_id', 'unit_barang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_unit');
    }
};
