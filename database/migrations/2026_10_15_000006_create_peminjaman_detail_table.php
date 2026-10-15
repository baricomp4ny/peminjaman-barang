<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jenis alat + jumlah yang diajukan peminjam
        Schema::create('peminjaman_detail', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('peminjaman_id')->constrained('peminjaman')->cascadeOnDelete();
            $table->foreignUlid('barang_id')->constrained('barang')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->timestamps();

            $table->unique(['peminjaman_id', 'barang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_detail');
    }
};
