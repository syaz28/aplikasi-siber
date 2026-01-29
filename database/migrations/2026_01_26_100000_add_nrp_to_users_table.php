<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add name column if not exists
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name')->after('id');
            }
            // Add nrp column if not exists
            if (!Schema::hasColumn('users', 'nrp')) {
                $table->string('nrp')->nullable()->unique()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nrp')) {
                $table->dropColumn('nrp');
            }
        });
    }
};
