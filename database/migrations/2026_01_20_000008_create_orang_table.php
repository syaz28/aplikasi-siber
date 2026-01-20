<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Orang (Persons - Generic)
 * 
 * Tabel generik untuk menyimpan data orang (pelapor, korban, tersangka)
 * Satu orang bisa menjadi pelapor di laporan A, korban di laporan B, tersangka di laporan C
 * NIK dan telepon di-encrypt menggunakan Laravel casting
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orang', function (Blueprint $table) {
            $table->id();
            $table->char('nik', 16)->unique()->comment('NIK (akan di-encrypt via model cast)');
            $table->string('nama', 100)->comment('Nama lengkap sesuai KTP');
            $table->string('tempat_lahir', 100)->comment('Tempat lahir');
            $table->date('tanggal_lahir')->comment('Tanggal lahir');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->comment('Jenis kelamin');
            $table->string('pekerjaan', 100)->comment('Pekerjaan');
            $table->string('telepon', 20)->comment('Nomor telepon (akan di-encrypt via model cast)');
            $table->string('email', 100)->nullable()->comment('Email (opsional)');
            $table->timestamps();
            
            // Indexes untuk searching dan filtering
            $table->index('telepon', 'orang_telepon_index');
            $table->index('nama', 'orang_nama_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orang');
    }
};
