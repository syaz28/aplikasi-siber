<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove deskripsi column from kategori_kejahatan table.
 * Field no longer needed - categories only use nama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kategori_kejahatan', function (Blueprint $table) {
            $table->dropColumn('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('kategori_kejahatan', function (Blueprint $table) {
            $table->text('deskripsi')->nullable()->after('nama')->comment('Deskripsi kategori');
        });
    }
};
