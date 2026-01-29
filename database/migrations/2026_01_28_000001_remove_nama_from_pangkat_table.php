<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Remove nama column from pangkat table
 * 
 * Simplify pangkat table to only store kode (singkatan)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pangkat', function (Blueprint $table) {
            $table->dropColumn('nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pangkat', function (Blueprint $table) {
            $table->string('nama', 100)->after('kode')->comment('Nama lengkap pangkat');
        });
    }
};
