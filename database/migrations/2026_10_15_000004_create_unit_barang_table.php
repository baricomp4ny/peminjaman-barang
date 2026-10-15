<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris = satu unit fisik = satu label QR
        Schema::create('unit_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang')->restrictOnDelete();
            $table->foreignId('lokasi_id')->constrained('lokasi')->restrictOnDelete();
            $table->string('kode_unit', 30)->unique(); // isi QR, contoh PRJ-001
            $table->enum('kondisi', ['baik', 'rusak', 'hilang'])->default('baik');
            $table->enum('status', ['tersedia', 'dipinjam', 'perbaikan', 'hilang'])->default('tersedia');
            $table->date('tanggal_perolehan')->nullable();
            $table->timestamps();

            $table->index(['barang_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_barang');
    }
};
