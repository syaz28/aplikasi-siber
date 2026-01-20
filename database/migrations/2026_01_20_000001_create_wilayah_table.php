<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Wilayah Indonesia (Hierarchical Single Table)
 * 
 * Source: github.com/cahyadsn/wilayah
 * Standard: Kepmendagri No 300.2.2-2138 Tahun 2025
 * 
 * Hierarchical Format:
 * - Provinsi: XX (2 char) → '33' (Jawa Tengah)
 * - Kabupaten/Kota: XX.XX (5 char) → '33.74' (Kota Semarang)
 * - Kecamatan: XX.XX.XX (8 char) → '33.74.01' (Semarang Tengah)
 * - Kelurahan/Desa: XX.XX.XX.XXXX (13 char) → '33.74.01.1001' (Miroto)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            // Primary key: hierarchical code (max 13 chars for kelurahan/desa)
            $table->string('kode', 13)->primary();
            
            // Region name
            $table->string('nama', 100);
            
            // Index for search performance
            $table->index('nama', 'wilayah_nama_idx');
        });
        
        // Add table comment
        DB::statement("ALTER TABLE `wilayah` COMMENT = 'Indonesian administrative regions - hierarchical single table (Kepmendagri standard)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
