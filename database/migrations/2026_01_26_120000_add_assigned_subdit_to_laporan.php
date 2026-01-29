<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan field untuk tracking assignment subdit pada laporan
     */
    public function up(): void
    {
        Schema::table('laporan', function (Blueprint $table) {
            // Subdit yang ditugaskan menangani laporan (1, 2, atau 3)
            $table->unsignedTinyInteger('assigned_subdit')->nullable()->after('status');
            
            // User (admin) yang melakukan assignment
            $table->foreignId('assigned_by')->nullable()->after('assigned_subdit')
                ->constrained('users')->nullOnDelete();
            
            // Waktu assignment
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            $table->dropColumn(['assigned_subdit', 'assigned_by', 'assigned_at']);
        });
    }
};
