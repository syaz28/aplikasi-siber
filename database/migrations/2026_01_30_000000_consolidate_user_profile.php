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
     * Consolidate anggota table into users table.
     * After this migration:
     * - users table contains all user profile data (nrp, pangkat, telepon, etc.)
     * - anggota table is dropped
     * - laporan.petugas_id now references users.id instead of anggota.id
     */
    public function up(): void
    {
        // Step 1: Add new columns to users table (if not exist)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'pangkat')) {
                $table->string('pangkat', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'telepon')) {
                $table->string('telepon', 20)->nullable()->after('pangkat');
            }
            if (!Schema::hasColumn('users', 'jabatan')) {
                $table->string('jabatan', 100)->nullable()->after('telepon');
            }
        });

        // Step 2: Drop foreign key on laporan.petugas_id first (before updating)
        // Use raw SQL to avoid errors when foreign key doesn't exist
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'laporan' 
            AND COLUMN_NAME = 'petugas_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE laporan DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // Step 3: Migrate data from anggota to users (only if anggota_id column exists)
        if (Schema::hasTable('anggota') && Schema::hasColumn('users', 'anggota_id')) {
            // Update users with anggota data
            DB::statement("
                UPDATE users u
                INNER JOIN anggota a ON u.anggota_id = a.id
                LEFT JOIN pangkat p ON a.pangkat_id = p.id
                LEFT JOIN jabatan j ON a.jabatan_id = j.id
                SET 
                    u.nrp = COALESCE(u.nrp, a.nrp),
                    u.name = COALESCE(NULLIF(u.name, ''), a.nama),
                    u.pangkat = p.nama,
                    u.jabatan = j.nama
                WHERE u.anggota_id IS NOT NULL
            ");

            // Step 4: Update laporan.petugas_id to point to users.id instead of anggota.id
            DB::statement("
                UPDATE laporan l
                INNER JOIN users u ON u.anggota_id = l.petugas_id
                SET l.petugas_id = u.id
                WHERE l.petugas_id IN (SELECT id FROM anggota)
            ");
        } elseif (Schema::hasTable('anggota')) {
            // anggota table exists but anggota_id column was already dropped
            // Update laporan.petugas_id based on NRP matching
            DB::statement("
                UPDATE laporan l
                INNER JOIN anggota a ON l.petugas_id = a.id
                INNER JOIN users u ON u.nrp = a.nrp
                SET l.petugas_id = u.id
            ");
        }

        // Step 5: Remove anggota_id column from users (if it still exists)
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'anggota_id')) {
                // Drop foreign key if exists
                try {
                    $table->dropForeign(['anggota_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->dropColumn('anggota_id');
            }
        });

        // Step 6: Drop foreign keys on anggota table before dropping it
        if (Schema::hasTable('anggota')) {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'anggota' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            foreach ($foreignKeys as $fk) {
                DB::statement("ALTER TABLE anggota DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }
        }

        // Step 7: Drop anggota table
        Schema::dropIfExists('anggota');

        // Step 8: Drop pangkat and jabatan tables (no longer needed as lookup tables)
        Schema::dropIfExists('pangkat');
        Schema::dropIfExists('jabatan');

        // Step 9: Make petugas_id nullable and add new foreign key on laporan.petugas_id to users table
        Schema::table('laporan', function (Blueprint $table) {
            $table->unsignedBigInteger('petugas_id')->nullable()->change();
        });

        // Set petugas_id to NULL for records where petugas_id doesn't exist in users table
        DB::statement("
            UPDATE laporan 
            SET petugas_id = NULL 
            WHERE petugas_id IS NOT NULL 
            AND petugas_id NOT IN (SELECT id FROM users)
        ");

        Schema::table('laporan', function (Blueprint $table) {
            $table->foreign('petugas_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate pangkat table
        Schema::create('pangkat', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 100);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // Recreate jabatan table
        Schema::create('jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('deskripsi')->nullable();
            $table->timestamps();
        });

        // Recreate anggota table
        Schema::create('anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pangkat_id')->constrained('pangkat');
            $table->foreignId('jabatan_id')->constrained('jabatan');
            $table->string('nrp', 20)->unique();
            $table->string('nama', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add anggota_id back to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('anggota_id')->nullable()->after('id')->constrained('anggota')->nullOnDelete();
        });

        // Remove new columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pangkat', 'telepon', 'jabatan']);
        });
    }
};
