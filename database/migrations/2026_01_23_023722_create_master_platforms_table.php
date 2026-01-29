<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_platforms', function (Blueprint $table) {
            $table->id();
            $table->enum('kategori', [
                'Media Sosial',
                'Nomor Telepon',
                'Rekening Bank',
                'E-Wallet',
                'Email',
                'Marketplace',
                'Lainnya'
            ])->index();
            $table->string('nama_platform', 100);
            $table->boolean('is_active')->default(true);
            $table->integer('urutan')->default(0); // For custom sorting
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['kategori', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_platforms');
    }
};
