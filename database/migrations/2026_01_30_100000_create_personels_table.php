<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Create personels table to store actual police personnel data.
     * This data was previously stored in users table but is now separated.
     */
    public function up(): void
    {
        Schema::create('personels', function (Blueprint $table) {
            $table->id();
            $table->string('nrp', 20)->unique()->comment('Nomor Registrasi Pokok');
            $table->string('nama_lengkap');
            $table->string('pangkat')->nullable()->comment('AKP, BRIPKA, IPDA, etc.');
            $table->unsignedInteger('subdit_id')->nullable()->comment('1=Siber Ekonomi, 2=Siber Sosial, 3=Siber Khusus');
            $table->unsignedInteger('unit_id')->nullable()->comment('Unit 1-5');
            $table->timestamps();
            
            // Indexes
            $table->index('subdit_id');
            $table->index('unit_id');
            $table->index('nrp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personels');
    }
};
