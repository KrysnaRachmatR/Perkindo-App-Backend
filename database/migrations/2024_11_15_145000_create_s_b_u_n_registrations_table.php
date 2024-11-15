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
        Schema::create('sbun_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('non_konstruksi_klasifikasi_id');
            $table->string('non_konstruksi_sub_klasifikasi_id');
            $table->string('akta_pendirian');
            $table->string('npwp_perusahaan');
            $table->string('ktp_penanggung_jawab');
            $table->string('npwp_penanggung_jawab');
            $table->string('foto_penanggung_jawab');
            $table->string('nomor_hp_penanggung_jawab');
            $table->string('ktp_pemegang_saham');
            $table->string('npwp_pemegang_saham');
            $table->string('email_perusahaan');
            $table->string('logo_perusahaan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbun_registrations');
    }
};
