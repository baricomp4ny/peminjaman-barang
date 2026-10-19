<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Path file surat permohonan peminjaman yang diunggah peminjam (disimpan di disk privat). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->string('surat_peminjaman')->nullable()->after('tujuan');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropColumn('surat_peminjaman');
        });
    }
};
