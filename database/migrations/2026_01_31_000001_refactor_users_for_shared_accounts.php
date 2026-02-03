<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Refactor users table to be a simple "Gatekeeper" for shared accounts.
     * Username-based login replaces email/NRP authentication.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop old columns that belong to personnel data (only if they exist)
            $columnsToCheck = ['nrp', 'pangkat', 'telepon', 'jabatan', 'email', 'email_verified_at', 'name'];
            $columnsToDrop = [];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
            
            // Add new columns for shared account model
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'petugas', 'admin_subdit', 'pimpinan'])->after('password');
            }
            if (!Schema::hasColumn('users', 'subdit_access')) {
                $table->unsignedInteger('subdit_access')->nullable()->after('role')->comment('For admin_subdit: 1=Siber Ekonomi, 2=Siber Sosial, 3=Siber Khusus');
            }
        });
        
        // Update password_reset_tokens to use username instead of email
        Schema::dropIfExists('password_reset_tokens');
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('username')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['username', 'role', 'subdit_access']);
            
            // Restore old columns
            $table->string('name')->after('id');
            $table->string('email')->unique()->after('name');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('nrp')->unique()->nullable()->after('email_verified_at');
            $table->string('pangkat')->nullable()->after('nrp');
            $table->string('telepon')->nullable()->after('pangkat');
            $table->string('jabatan')->nullable()->after('telepon');
        });
        
        // Restore email-based password reset
        Schema::dropIfExists('password_reset_tokens');
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
