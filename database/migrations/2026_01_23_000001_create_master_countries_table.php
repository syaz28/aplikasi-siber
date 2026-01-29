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
        Schema::create('master_countries', function (Blueprint $table) {
            $table->id();
            $table->char('alpha_2', 2)->index();
            $table->char('alpha_3', 3)->nullable();
            $table->string('name', 100);
            $table->string('phone_code', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_countries');
    }
};
