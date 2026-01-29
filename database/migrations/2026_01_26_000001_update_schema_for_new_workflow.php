<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates schema for new Role & Workflow system:
     * - Users: role-based access (admin, petugas, admin_subdit, pimpinan)
     * - Laporans: new status workflow with disposisi routing
     */
    public function up(): void
    {
        // =============================================
        // 1. UPDATE USERS TABLE
        // =============================================
        Schema::table('users', function (Blueprint $table) {
            // Drop old jabatan column if exists
            if (Schema::hasColumn('users', 'jabatan')) {
                $table->dropColumn('jabatan');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            // Add role column with enum (if not exists)
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'petugas', 'admin_subdit', 'pimpinan'])
                    ->default('petugas')
                    ->after('password')
                    ->comment('User role for access control');
            }

            // Add subdit column (for Admin Subdit - which subdit they manage)
            if (!Schema::hasColumn('users', 'subdit')) {
                $table->unsignedTinyInteger('subdit')
                    ->nullable()
                    ->after('role')
                    ->comment('Subdit assignment (1-3) for admin_subdit role');
            }

            // Add unit column (for Petugas - which unit they belong to)
            if (!Schema::hasColumn('users', 'unit')) {
                $table->unsignedTinyInteger('unit')
                    ->nullable()
                    ->after('subdit')
                    ->comment('Unit assignment (1-5) for petugas role');
            }
        });

        // =============================================
        // 2. UPDATE LAPORAN TABLE
        // =============================================
        
        // First, modify the status column enum values
        // MySQL requires dropping and recreating enum to change values
        DB::statement("ALTER TABLE laporan MODIFY COLUMN status ENUM('Draft', 'Penyelidikan', 'Penyidikan', 'Tahap I', 'Tahap II', 'SP3', 'RJ', 'Diversi') DEFAULT 'Penyelidikan'");

        // Migrate existing status values to new enum
        DB::statement("UPDATE laporan SET status = 'Penyelidikan' WHERE status NOT IN ('Draft', 'Penyelidikan', 'Penyidikan', 'Tahap I', 'Tahap II', 'SP3', 'RJ', 'Diversi')");

        Schema::table('laporan', function (Blueprint $table) {
            // Add disposisi_subdit column (filled by Admin)
            if (!Schema::hasColumn('laporan', 'disposisi_subdit')) {
                $table->unsignedTinyInteger('disposisi_subdit')
                    ->nullable()
                    ->after('status')
                    ->comment('Disposisi to Subdit (1-3) assigned by Admin');
            }

            // Add disposisi_unit column (filled by Admin Subdit)
            if (!Schema::hasColumn('laporan', 'disposisi_unit')) {
                $table->unsignedTinyInteger('disposisi_unit')
                    ->nullable()
                    ->after('disposisi_subdit')
                    ->comment('Disposisi to Unit (1-5) assigned by Admin Subdit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // =============================================
        // REVERT LAPORAN TABLE
        // =============================================
        Schema::table('laporan', function (Blueprint $table) {
            $table->dropColumn(['disposisi_subdit', 'disposisi_unit']);
        });

        // Revert status enum to original values
        DB::statement("ALTER TABLE laporan MODIFY COLUMN status ENUM('draft', 'submitted', 'verified', 'investigating', 'closed', 'rejected') DEFAULT 'draft'");

        // =============================================
        // REVERT USERS TABLE
        // =============================================
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'subdit', 'unit']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Restore jabatan column
            $table->string('jabatan')->nullable()->after('password');
        });
    }
};
