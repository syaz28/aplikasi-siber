<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add pendidikan column to orang table
 * 
 * Adds standardized education level field for analytics.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orang', function (Blueprint $table) {
            $table->string('pendidikan', 50)->nullable()->after('pekerjaan')
                ->comment('Pendidikan terakhir (standardized)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orang', function (Blueprint $table) {
            $table->dropColumn('pendidikan');
        });
    }
};
