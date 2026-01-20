<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tersangka (Suspects per Report)
 * 
 * Tabel tersangka per laporan (1:N relation)
 * - Satu laporan bisa punya banyak tersangka
 * - orang_id NULLABLE: tersangka bisa belum teridentifikasi (hanya punya identitas digital)
 * - Identitas digital tersangka disimpan di tabel terpisah (identitas_tersangka)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tersangka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')
                ->constrained('laporan')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->comment('FK ke laporan');
            $table->foreignId('orang_id')
                ->nullable()
                ->constrained('orang')
                ->onDelete('set null')
                ->onUpdate('cascade')
                ->comment('FK ke orang (tersangka) - nullable jika belum teridentifikasi');
            
            $table->text('catatan')->nullable()->comment('Catatan tentang tersangka');
            $table->timestamps();
            
            // Indexes
            $table->index('laporan_id', 'tersangka_laporan_id_index');
            $table->index('orang_id', 'tersangka_orang_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tersangka');
    }
};
