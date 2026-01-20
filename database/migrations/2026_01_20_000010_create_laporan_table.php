<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Laporan Kejahatan Siber (Cyber Crime Reports)
 * 
 * Tabel utama untuk laporan kejahatan siber
 * - Pelapor bisa berbeda dengan korban (hubungan_pelapor)
 * - Lokasi kejadian dengan wilayah denormalized untuk analytics
 * - STPA number bisa manual input atau kosong
 * - Audit trail: created_by, updated_by
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laporan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_stpa', 50)->nullable()->unique()->comment('Nomor STPA (opsional, bisa manual input)');
            $table->dateTime('tanggal_laporan')->comment('Tanggal laporan dibuat');
            
            // Pelapor (reporter)
            $table->foreignId('pelapor_id')
                ->constrained('orang')
                ->onDelete('restrict')
                ->onUpdate('cascade')
                ->comment('FK ke orang (pelapor)');
            $table->enum('hubungan_pelapor', ['diri_sendiri', 'keluarga', 'kuasa_hukum', 'teman', 'rekan_kerja', 'lainnya'])
                ->default('diri_sendiri')
                ->comment('Hubungan pelapor dengan korban');
            
            // Petugas penerima laporan
            $table->foreignId('petugas_id')
                ->constrained('anggota')
                ->onDelete('restrict')
                ->onUpdate('cascade')
                ->comment('FK ke anggota (petugas penerima)');
            
            // Jenis kejahatan
            $table->foreignId('jenis_kejahatan_id')
                ->constrained('jenis_kejahatan')
                ->onDelete('restrict')
                ->onUpdate('cascade')
                ->comment('FK ke jenis_kejahatan');
            
            // Lokasi kejadian dengan wilayah denormalized (nullable - bisa online crime tanpa lokasi fisik)
            $table->string('kode_provinsi_kejadian', 2)->nullable()->comment('Contoh: 33');
            $table->string('kode_kabupaten_kejadian', 5)->nullable()->comment('Contoh: 33.74');
            $table->string('kode_kecamatan_kejadian', 8)->nullable()->comment('Contoh: 33.74.01');
            $table->string('kode_kelurahan_kejadian', 13)->nullable()->comment('Contoh: 33.74.01.1001');
            $table->text('alamat_kejadian')->nullable()->comment('Detail alamat kejadian');
            
            // Detail kejadian
            $table->dateTime('waktu_kejadian')->comment('Waktu kejadian terjadi');
            $table->text('modus')->comment('Modus operandi kejahatan');
            
            // Status workflow
            $table->enum('status', ['draft', 'submitted', 'verified', 'investigating', 'closed', 'rejected'])
                ->default('draft')
                ->comment('Status laporan');
            
            $table->text('catatan')->nullable()->comment('Catatan tambahan');
            $table->timestamps();
            
            // Audit trail
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->onUpdate('cascade')
                ->comment('User pembuat laporan');
            $table->foreignId('updated_by')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->onUpdate('cascade')
                ->comment('User terakhir update');
            
            // Indexes untuk filtering dan searching
            $table->index('pelapor_id', 'laporan_pelapor_id_index');
            $table->index('petugas_id', 'laporan_petugas_id_index');
            $table->index('jenis_kejahatan_id', 'laporan_jenis_kejahatan_id_index');
            $table->index('kode_provinsi_kejadian', 'laporan_kode_provinsi_kejadian_index');
            $table->index('kode_kabupaten_kejadian', 'laporan_kode_kabupaten_kejadian_index');
            $table->index('kode_kecamatan_kejadian', 'laporan_kode_kecamatan_kejadian_index');
            $table->index('kode_kelurahan_kejadian', 'laporan_kode_kelurahan_kejadian_index');
            $table->index('tanggal_laporan', 'laporan_tanggal_laporan_index');
            $table->index('status', 'laporan_status_index');
            
            // Foreign keys ke wilayah (nullable)
            $table->foreign('kode_provinsi_kejadian', 'laporan_kode_provinsi_kejadian_foreign')
                ->references('kode')->on('wilayah')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('kode_kabupaten_kejadian', 'laporan_kode_kabupaten_kejadian_foreign')
                ->references('kode')->on('wilayah')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('kode_kecamatan_kejadian', 'laporan_kode_kecamatan_kejadian_foreign')
                ->references('kode')->on('wilayah')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('kode_kelurahan_kejadian', 'laporan_kode_kelurahan_kejadian_foreign')
                ->references('kode')->on('wilayah')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan');
    }
};
