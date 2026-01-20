<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Identitas Tersangka (Digital Identities of Suspects)
 * 
 * Tabel identitas digital tersangka (1:N relation dengan tersangka)
 * - Satu tersangka bisa punya banyak identitas digital
 * - Jenis: telepon, rekening, sosmed, email, ewallet, kripto, marketplace, website, lainnya
 * - Digunakan untuk tracking tersangka berulang across reports
 * - Query by jenis+nilai untuk menemukan tersangka yang sama di laporan berbeda
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('identitas_tersangka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tersangka_id')
                ->constrained('tersangka')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->comment('FK ke tersangka');
            
            $table->enum('jenis', [
                'telepon',
                'rekening',
                'sosmed',
                'email',
                'ewallet',
                'kripto',
                'marketplace',
                'website',
                'lainnya'
            ])->comment('Jenis identitas digital');
            
            $table->string('nilai', 255)->comment('Nilai identitas (nomor HP, rekening, username, dll)');
            $table->string('platform', 100)->nullable()->comment('Platform (BRI, Instagram, Tokopedia, dll)');
            $table->string('nama_akun', 100)->nullable()->comment('Nama akun/pemilik');
            $table->text('catatan')->nullable()->comment('Catatan tambahan');
            $table->timestamps();
            
            // Indexes untuk searching dan suspect linkage
            $table->index('tersangka_id', 'identitas_tersangka_tersangka_id_index');
            $table->index('nilai', 'identitas_tersangka_nilai_index');
            $table->index('jenis', 'identitas_tersangka_jenis_index');
            $table->index(['jenis', 'nilai'], 'identitas_tersangka_jenis_nilai_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identitas_tersangka');
    }
};
