<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Fitur denda tidak dipakai lagi: kolom denda dihapus (hari_terlambat tetap),
 * pengaturan tarif denda diganti lama penangguhan setelah terlambat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman_unit', function (Blueprint $table) {
            $table->dropColumn('denda');
        });

        DB::table('pengaturan')->where('key', 'denda_per_hari')->delete();
        DB::table('pengaturan')->insertOrIgnore([
            'id' => strtolower((string) Str::ulid()),
            'key' => 'lama_penangguhan_hari',
            'value' => '7',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('peminjaman_unit', function (Blueprint $table) {
            $table->unsignedBigInteger('denda')->default(0)->after('hari_terlambat');
        });

        DB::table('pengaturan')->where('key', 'lama_penangguhan_hari')->delete();
        DB::table('pengaturan')->insertOrIgnore([
            'id' => strtolower((string) Str::ulid()),
            'key' => 'denda_per_hari',
            'value' => '1000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
