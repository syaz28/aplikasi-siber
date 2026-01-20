<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Modify Users Table - Add Anggota Link & Role
 * 
 * Modifikasi tabel users untuk menambahkan:
 * - anggota_id: Link ke tabel anggota (nullable)
 * - role: superadmin, admin, operator
 * - is_active: Status aktif user
 * - Hapus field 'name' (nama diambil dari anggota)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop name column (nama diambil dari relasi anggota)
            $table->dropColumn('name');
            
            // Add anggota_id FK (nullable - bisa user tanpa anggota untuk superadmin)
            $table->foreignId('anggota_id')
                ->nullable()
                ->after('id')
                ->constrained('anggota')
                ->onDelete('set null')
                ->onUpdate('cascade')
                ->comment('FK ke anggota (nullable untuk superadmin)');
            
            // Add role enum
            $table->enum('role', ['superadmin', 'admin', 'operator'])
                ->default('operator')
                ->after('password')
                ->comment('Role pengguna sistem');
            
            // Add is_active flag
            $table->boolean('is_active')
                ->default(true)
                ->after('remember_token')
                ->comment('Status aktif user');
            
            // Modify email length to match schema
            $table->string('email', 100)->change();
            
            // Add indexes
            $table->index('anggota_id', 'users_anggota_id_index');
            $table->index('role', 'users_role_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('users_anggota_id_index');
            $table->dropIndex('users_role_index');
            
            // Drop added columns
            $table->dropForeign(['anggota_id']);
            $table->dropColumn(['anggota_id', 'role', 'is_active']);
            
            // Restore name column
            $table->string('name')->after('id');
            
            // Restore email length
            $table->string('email', 255)->change();
        });
    }
};
