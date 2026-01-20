<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Kategori Kejahatan Siber (Cyber Crime Categories)
 * 
 * Master data untuk kategori kejahatan siber (8 kategori utama)
 * Contoh: Penipuan Online, Pemerasan Digital, Pencurian Data, dll
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kategori_kejahatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique()->comment('Nama kategori kejahatan');
            $table->text('deskripsi')->nullable()->comment('Deskripsi kategori');
            $table->boolean('is_active')->default(true)->comment('Status aktif kategori');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_kejahatan');
    }
};
