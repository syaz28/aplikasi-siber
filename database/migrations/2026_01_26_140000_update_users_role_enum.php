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
     * Update ENUM role dari (superadmin, admin, operator) 
     * menjadi (admin, petugas, admin_subdit, pimpinan)
     */
    public function up(): void
    {
        // Mapping role lama ke role baru
        // superadmin -> admin
        // admin -> admin_subdit  
        // operator -> petugas
        
        // Step 1: Ubah ke string dulu agar bisa update values
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50)");
        
        // Step 2: Update existing values
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'operator')->update(['role' => 'petugas']);
        // 'admin' tetap 'admin' atau bisa jadi 'admin_subdit' tergantung kebutuhan
        // Untuk sementara, admin lama tetap jadi admin
        
        // Step 3: Ubah ke ENUM baru
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'petugas', 'admin_subdit', 'pimpinan') DEFAULT 'petugas'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback ke enum lama
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50)");
        
        // Revert values
        DB::table('users')->where('role', 'admin')->update(['role' => 'superadmin']);
        DB::table('users')->where('role', 'petugas')->update(['role' => 'operator']);
        DB::table('users')->where('role', 'admin_subdit')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'pimpinan')->update(['role' => 'admin']);
        
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'operator') DEFAULT 'operator'");
    }
};
