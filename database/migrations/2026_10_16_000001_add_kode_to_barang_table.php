<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prefix kode unit, misal PRJ → unit PRJ-001, PRJ-002, ...
        Schema::table('barang', function (Blueprint $table) {
            $table->string('kode', 5)->unique()->after('kategori_id');
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->dropUnique(['kode']);
            $table->dropColumn('kode');
        });
    }
};
