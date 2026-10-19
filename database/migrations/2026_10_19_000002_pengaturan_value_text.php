<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Nilai pengaturan bisa berupa teks panjang (syarat peminjaman), jadi value diubah dari VARCHAR ke TEXT. */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE pengaturan MODIFY `value` TEXT NULL');
    }

    public function down(): void
    {
        DB::table('pengaturan')->where('key', 'syarat_peminjaman')->delete();
        DB::statement('ALTER TABLE pengaturan MODIFY `value` VARCHAR(255) NULL');
    }
};
