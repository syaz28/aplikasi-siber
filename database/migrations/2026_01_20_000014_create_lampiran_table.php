<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Lampiran (Evidence Attachments)
 * 
 * Tabel lampiran bukti per laporan (1:N relation)
 * - Satu laporan bisa punya banyak lampiran
 * - Jenis: gambar, dokumen, screenshot, video, audio, lainnya
 * - File disimpan di storage, path disimpan di database
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lampiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')
                ->constrained('laporan')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->comment('FK ke laporan');
            
            $table->string('nama_file', 255)->comment('Nama file asli');
            $table->string('path_file', 500)->comment('Path file di storage');
            $table->enum('jenis_file', [
                'gambar',
                'dokumen',
                'screenshot',
                'video',
                'audio',
                'lainnya'
            ])->comment('Jenis file bukti');
            $table->unsignedInteger('ukuran_file')->nullable()->comment('Ukuran file dalam bytes');
            $table->text('deskripsi')->nullable()->comment('Deskripsi lampiran');
            $table->timestamps();
            
            // Indexes
            $table->index('laporan_id', 'lampiran_laporan_id_index');
            $table->index('jenis_file', 'lampiran_jenis_file_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lampiran');
    }
};
