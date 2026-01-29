<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Master Pendidikan (Last Education Master Data)
 * 
 * Standardized education level list for analytics purposes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_pendidikan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique()->comment('Nama tingkat pendidikan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_pendidikan');
    }
};
