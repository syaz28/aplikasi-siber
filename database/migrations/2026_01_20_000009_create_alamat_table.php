<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Alamat (Addresses with Denormalized Wilayah)
 * 
 * Alamat orang dengan kode wilayah denormalized untuk performa query
 * Setiap orang bisa punya 2 alamat: KTP dan Domisili
 * Foreign key ke wilayah untuk validasi dan join ke nama wilayah
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alamat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orang_id')
                ->constrained('orang')
                ->onDelete('cascade')
                ->onUpdate('cascade')
                ->comment('FK ke orang');
            $table->enum('jenis_alamat', ['ktp', 'domisili'])
                ->comment('Jenis alamat: sesuai KTP atau domisili aktual');
            
            // Denormalized wilayah codes untuk fast analytics
            $table->string('kode_provinsi', 2)->comment('Contoh: 33');
            $table->string('kode_kabupaten', 5)->comment('Contoh: 33.74');
            $table->string('kode_kecamatan', 8)->comment('Contoh: 33.74.01');
            $table->string('kode_kelurahan', 13)->comment('Contoh: 33.74.01.1001');
            
            $table->text('detail_alamat')->comment('Jalan, RT/RW, No. Rumah, dll');
            $table->timestamps();
            
            // Composite unique: satu orang hanya bisa punya 1 alamat per jenis
            $table->unique(['orang_id', 'jenis_alamat'], 'alamat_orang_jenis_unique');
            
            // Indexes untuk filtering by wilayah
            $table->index('orang_id', 'alamat_orang_id_index');
            $table->index('kode_provinsi', 'alamat_kode_provinsi_index');
            $table->index('kode_kabupaten', 'alamat_kode_kabupaten_index');
            $table->index('kode_kecamatan', 'alamat_kode_kecamatan_index');
            $table->index('kode_kelurahan', 'alamat_kode_kelurahan_index');
            
            // Foreign keys ke wilayah untuk validasi
            $table->foreign('kode_provinsi', 'alamat_kode_provinsi_foreign')
                ->references('kode')->on('wilayah')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('kode_kabupaten', 'alamat_kode_kabupaten_foreign')
                ->references('kode')->on('wilayah')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('kode_kecamatan', 'alamat_kode_kecamatan_foreign')
                ->references('kode')->on('wilayah')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('kode_kelurahan', 'alamat_kode_kelurahan_foreign')
                ->references('kode')->on('wilayah')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alamat');
    }
};
