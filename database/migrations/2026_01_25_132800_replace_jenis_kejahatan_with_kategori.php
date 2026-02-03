<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Replace jenis_kejahatan_id with kategori_kejahatan_id in laporan table
 * 
 * Jenis Kejahatan tidak lagi digunakan - hanya Kategori Kejahatan yang terpakai
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if kategori_kejahatan_id column already exists
        if (!Schema::hasColumn('laporan', 'kategori_kejahatan_id')) {
            // 1. Add kategori_kejahatan_id column to laporan table
            Schema::table('laporan', function (Blueprint $table) {
                $table->foreignId('kategori_kejahatan_id')
                    ->nullable()
                    ->after('petugas_id')
                    ->constrained('kategori_kejahatan')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');
            });
        }

        // 2. Migrate existing data if jenis_kejahatan table exists
        if (Schema::hasTable('jenis_kejahatan') && Schema::hasColumn('laporan', 'jenis_kejahatan_id')) {
            DB::statement('
                UPDATE laporan l
                INNER JOIN jenis_kejahatan jk ON l.jenis_kejahatan_id = jk.id
                SET l.kategori_kejahatan_id = jk.kategori_kejahatan_id
            ');
        }

        // 3. Remove jenis_kejahatan_id from laporan if it exists
        if (Schema::hasColumn('laporan', 'jenis_kejahatan_id')) {
            Schema::table('laporan', function (Blueprint $table) {
                $table->dropForeign(['jenis_kejahatan_id']);
                $table->dropIndex('laporan_jenis_kejahatan_id_index');
                $table->dropColumn('jenis_kejahatan_id');
            });
        }

        // 4. Add index for kategori_kejahatan_id if it doesn't exist
        $indexes = DB::select("SHOW INDEX FROM laporan WHERE Key_name = 'laporan_kategori_kejahatan_id_index'");
        if (empty($indexes)) {
            Schema::table('laporan', function (Blueprint $table) {
                $table->index('kategori_kejahatan_id', 'laporan_kategori_kejahatan_id_index');
            });
        }

        // 5. Drop jenis_kejahatan table
        Schema::dropIfExists('jenis_kejahatan');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Recreate jenis_kejahatan table
        Schema::create('jenis_kejahatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_kejahatan_id')->constrained('kategori_kejahatan');
            $table->string('nama', 100);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('kategori_kejahatan_id');
            $table->unique(['kategori_kejahatan_id', 'nama']);
        });

        // 2. Add jenis_kejahatan_id back to laporan
        Schema::table('laporan', function (Blueprint $table) {
            $table->foreignId('jenis_kejahatan_id')
                ->nullable()
                ->after('petugas_id')
                ->constrained('jenis_kejahatan');
            $table->index('jenis_kejahatan_id', 'laporan_jenis_kejahatan_id_index');
        });

        // 3. Remove kategori_kejahatan_id from laporan
        Schema::table('laporan', function (Blueprint $table) {
            $table->dropForeign(['kategori_kejahatan_id']);
            $table->dropIndex('laporan_kategori_kejahatan_id_index');
            $table->dropColumn('kategori_kejahatan_id');
        });
    }
};
