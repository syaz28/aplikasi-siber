<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Master Pekerjaan (Occupation Master Data)
 * 
 * Standardized occupation list for analytics purposes.
 * Replaces free-text input with dropdown selection.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique()->comment('Nama pekerjaan standar');
            $table->timestamps();
            
            $table->index('nama', 'master_pekerjaan_nama_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_pekerjaan');
    }
};
