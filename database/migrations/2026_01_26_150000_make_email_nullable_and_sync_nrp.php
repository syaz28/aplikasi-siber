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
     * 1. Make email nullable
     * 2. Sync NRP from anggota table based on anggota_id
     */
    public function up(): void
    {
        // Step 1: Make email nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        // Step 2: Sync NRP from anggota for users that have anggota_id
        DB::statement("
            UPDATE users u
            INNER JOIN anggota a ON u.anggota_id = a.id
            SET u.nrp = a.nrp, u.name = a.nama
            WHERE u.anggota_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
