<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Anggota Kepolisian (Police Members/Officers)
 * 
 * Data anggota Kepolisian yang bertugas di Ditresiber POLDA Jateng
 * Setiap anggota memiliki pangkat, jabatan, dan NRP unik
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pangkat_id')
                ->constrained('pangkat')
                ->onDelete('restrict')
                ->onUpdate('cascade')
                ->comment('FK ke pangkat');
            $table->foreignId('jabatan_id')
                ->constrained('jabatan')
                ->onDelete('restrict')
                ->onUpdate('cascade')
                ->comment('FK ke jabatan');
            $table->string('nrp', 20)->unique()->comment('Nomor Registrasi Pokok (NRP)');
            $table->string('nama', 100)->comment('Nama lengkap anggota');
            $table->boolean('is_active')->default(true)->comment('Status aktif anggota');
            $table->timestamps();
            
            // Indexes untuk filtering dan searching
            $table->index('pangkat_id', 'anggota_pangkat_id_index');
            $table->index('jabatan_id', 'anggota_jabatan_id_index');
            $table->index('is_active', 'anggota_is_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};
