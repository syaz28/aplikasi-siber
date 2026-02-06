<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing data to UPPERCASE
        DB::table('orang')
            ->where('jenis_kelamin', 'Laki-laki')
            ->update(['jenis_kelamin' => 'LAKI-LAKI']);
        
        DB::table('orang')
            ->where('jenis_kelamin', 'Perempuan')
            ->update(['jenis_kelamin' => 'PEREMPUAN']);
        
        // Then, modify the enum column to use uppercase values
        DB::statement("ALTER TABLE orang MODIFY COLUMN jenis_kelamin ENUM('LAKI-LAKI', 'PEREMPUAN') NOT NULL COMMENT 'Jenis kelamin'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original values
        DB::table('orang')
            ->where('jenis_kelamin', 'LAKI-LAKI')
            ->update(['jenis_kelamin' => 'Laki-laki']);
        
        DB::table('orang')
            ->where('jenis_kelamin', 'PEREMPUAN')
            ->update(['jenis_kelamin' => 'Perempuan']);
        
        // Revert the enum column
        DB::statement("ALTER TABLE orang MODIFY COLUMN jenis_kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL COMMENT 'Jenis kelamin'");
    }
};
