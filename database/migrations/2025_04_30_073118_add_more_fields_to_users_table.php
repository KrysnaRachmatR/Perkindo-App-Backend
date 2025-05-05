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
            $table->string('no_hp_direktur')->nullable();
            $table->string('email_perusahaan')->nullable();
            $table->string('no_hp_perusahaan')->nullable();
            $table->string('logo_perusahaan')->nullable();
            $table->string('no_hp_penanggung_jawab')->nullable();
            $table->string('ktp_penanggung_jawab')->nullable();
            $table->string('npwp_penanggung_jawab')->nullable();
            $table->string('nama_pemegang_saham')->nullable();
            $table->string('no_hp_pemegang_saham')->nullable();
            $table->string('ktp_pemegang_saham')->nullable();
            $table->string('npwp_pemegang_saham')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
