<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aturan bisnis yang bisa diubah admin (lama pinjam, denda, dll.)
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key', 50)->unique();
            $table->string('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
