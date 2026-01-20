<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Pangkat Kepolisian (Police Ranks)
 * 
 * Master data untuk pangkat anggota Kepolisian Republik Indonesia
 * Urutan: 1 = tertinggi (Jenderal) hingga 16 = terendah (Bripda)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pangkat', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique()->comment('Kode pangkat: AKBP, KOMPOL, AKP, dll');
            $table->string('nama', 100)->comment('Nama lengkap pangkat');
            $table->integer('urutan')->comment('Urutan hierarki: 1=tertinggi');
            $table->timestamps();
            
            // Index untuk sorting by hierarchy
            $table->index('urutan', 'pangkat_urutan_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pangkat');
    }
};
