<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Korban (Victims per Report)
 * 
 * Tabel korban per laporan (1:N relation)
 * - Satu laporan bisa punya banyak korban
 * - Kerugian disimpan per korban (bukan per laporan)
 * - Kerugian terbilang di-generate otomatis via TerbilangService
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('korban', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')
                ->constrained('laporan')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->comment('FK ke laporan');
            $table->foreignId('orang_id')
                ->constrained('orang')
                ->onDelete('restrict')
                ->onUpdate('cascade')
                ->comment('FK ke orang (korban)');
            
            // Kerugian finansial
            $table->decimal('kerugian_nominal', 20, 2)->default(0)->comment('Kerugian dalam rupiah');
            $table->string('kerugian_terbilang', 255)->nullable()->comment('Kerugian dalam kata (auto-generated)');
            
            $table->text('keterangan')->nullable()->comment('Keterangan tambahan tentang korban');
            $table->timestamps();
            
            // Composite unique: satu orang tidak bisa jadi korban 2x di laporan yang sama
            $table->unique(['laporan_id', 'orang_id'], 'korban_laporan_orang_unique');
            
            // Indexes
            $table->index('laporan_id', 'korban_laporan_id_index');
            $table->index('orang_id', 'korban_orang_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('korban');
    }
};
