<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jenis alat (misal: Mikroskop Stereo). Unit fisiknya ada di unit_barang.
        Schema::create('barang', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('kategori_id')->constrained('kategori')->restrictOnDelete();
            $table->string('kode', 5)->unique(); // prefix kode unit, misal MKR → MKR-001
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable(); // path file di storage
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
