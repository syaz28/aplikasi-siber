<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Fix Orang Encrypted Columns
 * 
 * Mengubah kolom nik dan telepon menjadi TEXT untuk menyimpan data terenkripsi
 * Laravel encryption menghasilkan string yang sangat panjang
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orang', function (Blueprint $table) {
            // Drop the unique index on nik first
            $table->dropUnique('orang_nik_unique');
            
            // Drop the index on telepon
            $table->dropIndex('orang_telepon_index');
        });

        Schema::table('orang', function (Blueprint $table) {
            // Change nik from CHAR(16) to TEXT for encrypted storage
            $table->text('nik')->change();
            
            // Change telepon from VARCHAR(20) to TEXT for encrypted storage
            $table->text('telepon')->change();
        });

        // Note: We cannot add unique constraint on TEXT columns in MySQL
        // Uniqueness will be enforced at application level
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orang', function (Blueprint $table) {
            $table->char('nik', 16)->change();
            $table->string('telepon', 20)->change();
        });

        Schema::table('orang', function (Blueprint $table) {
            $table->unique('nik', 'orang_nik_unique');
            $table->index('telepon', 'orang_telepon_index');
        });
    }
};
