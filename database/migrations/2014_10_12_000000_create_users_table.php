<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // ULID: unik, tidak bisa ditebak, tetap urut waktu (bukan auto increment)
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable(); // null untuk peminjam (tidak login)
            $table->enum('role', ['admin', 'peminjam'])->default('peminjam');
            $table->string('nomor_identitas', 50)->nullable()->unique(); // NIM/NIP, kunci pencocokan peminjam
            $table->string('unit', 100)->nullable(); // prodi/bagian
            $table->string('no_hp', 20)->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
