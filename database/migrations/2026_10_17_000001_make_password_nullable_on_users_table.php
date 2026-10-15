<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Peminjam tidak lagi punya akun login (mengajukan lewat halaman publik),
     * sehingga password hanya wajib untuk admin.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET password = '' WHERE password IS NULL");
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL');
    }
};
