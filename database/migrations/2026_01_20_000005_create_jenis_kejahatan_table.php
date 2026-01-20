<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Jenis Kejahatan Siber (Specific Cyber Crime Types)
 * 
 * Master data untuk jenis kejahatan siber spesifik (30+ jenis)
 * Setiap jenis belongs to satu kategori
 * Contoh: Penipuan Investasi Bodong, Sextortion, Phishing, dll
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jenis_kejahatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_kejahatan_id')
                ->constrained('kategori_kejahatan')
                ->onDelete('restrict')
                ->onUpdate('cascade')
                ->comment('FK ke kategori_kejahatan');
            $table->string('nama', 100)->comment('Nama jenis kejahatan');
            $table->text('deskripsi')->nullable()->comment('Deskripsi jenis kejahatan');
            $table->boolean('is_active')->default(true)->comment('Status aktif jenis');
            $table->timestamps();
            
            // Composite unique: kategori + nama
            $table->unique(['kategori_kejahatan_id', 'nama'], 'jenis_kejahatan_kategori_nama_unique');
            
            // Index untuk filtering by kategori
            $table->index('kategori_kejahatan_id', 'jenis_kejahatan_kategori_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_kejahatan');
    }
};
