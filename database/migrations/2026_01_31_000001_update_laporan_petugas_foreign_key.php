<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Update laporan.petugas_id foreign key
 * 
 * Changes foreign key from anggota table to personels table
 * This is part of the shared account refactor
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Drop existing foreign key constraint
        Schema::table('laporan', function (Blueprint $table) {
            // Try to drop the foreign key - it might have different names
            try {
                $table->dropForeign('laporan_petugas_id_foreign');
            } catch (\Exception $e) {
                // Ignore if doesn't exist with this name
            }
        });
        
        // Also try with raw SQL in case Laravel naming differs
        try {
            DB::statement('ALTER TABLE laporan DROP FOREIGN KEY laporan_petugas_id_foreign');
        } catch (\Exception $e) {
            // Ignore
        }
        
        // Step 2: Add new foreign key pointing to personels table
        Schema::table('laporan', function (Blueprint $table) {
            $table->foreign('petugas_id', 'laporan_petugas_id_foreign')
                ->references('id')
                ->on('personels')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to anggota table
        Schema::table('laporan', function (Blueprint $table) {
            $table->dropForeign('laporan_petugas_id_foreign');
        });
        
        Schema::table('laporan', function (Blueprint $table) {
            $table->foreign('petugas_id', 'laporan_petugas_id_foreign')
                ->references('id')
                ->on('anggota')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }
};
