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
            $table->dropColumn([
                'ktp_penanggung_jawab',
                'npwp_penanggung_jawab',
                'ktp_pemegang_saham',
                'npwp_pemegang_saham',
                
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ktp_penanggung_jawab')->nullable();
            $table->string('npwp_penanggung_jawab')->nullable();
            $table->string('ktp_pemegang_saham')->nullable();
            $table->string('npwp_pemegang_saham')->nullable();
        });
    }
};
