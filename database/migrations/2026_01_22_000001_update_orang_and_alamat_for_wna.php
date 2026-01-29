<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Update Orang and Alamat for WNA Support
 * 
 * Adds support for foreigners (WNA) alongside Indonesian citizens (WNI).
 * NOTE: Checks if columns exist before adding to handle partial migration recovery.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new columns to orang table if not exists
        if (!Schema::hasColumn('orang', 'kewarganegaraan')) {
            Schema::table('orang', function (Blueprint $table) {
                $table->enum('kewarganegaraan', ['WNI', 'WNA'])
                    ->default('WNI')
                    ->after('nama')
                    ->comment('Kewarganegaraan: WNI (Indonesia) atau WNA (Asing)');
            });
        }
        
        if (!Schema::hasColumn('orang', 'negara_asal')) {
            Schema::table('orang', function (Blueprint $table) {
                $table->string('negara_asal', 50)
                    ->nullable()
                    ->after('kewarganegaraan')
                    ->comment('Negara asal untuk WNA');
            });
        }

        // Add negara column to alamat table if not exists
        if (!Schema::hasColumn('alamat', 'negara')) {
            Schema::table('alamat', function (Blueprint $table) {
                $table->string('negara', 50)
                    ->default('Indonesia')
                    ->after('jenis_alamat')
                    ->comment('Negara alamat (default Indonesia)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('alamat', 'negara')) {
            Schema::table('alamat', function (Blueprint $table) {
                $table->dropColumn('negara');
            });
        }

        Schema::table('orang', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('orang', 'kewarganegaraan')) {
                $columns[] = 'kewarganegaraan';
            }
            if (Schema::hasColumn('orang', 'negara_asal')) {
                $columns[] = 'negara_asal';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
