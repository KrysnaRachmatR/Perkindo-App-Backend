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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan');
            $table->string('nama_direktur');
            $table->string('no_hp_direktur')->nullable();
            $table->string('no_hp_perusahaan')->nullable();
            $table->string('alamat_perusahaan');
            $table->string('logo_perusahaan')->nullable();

            $table->string('nama_penanggung_jawab');
            $table->string('no_hp_penanggung_jawab');
            $table->string('ktp_penanggung_jawab')->nullable();
            $table->string('npwp_penanggung_jawab')->nullable();

            $table->string('nama_pemegang_saham');
            $table->string('no_hp_pemegang_saham');
            $table->string('ktp_pemegang_saham');
            $table->string('npwp_pemegang_saham');

            $table->string('email')->unique();
            $table->string('password');

            $table->boolean('is_pengurus')->default(false)->comment('0 = bukan pengurus, 1 = pengurus');
            $table->string('jabatan')->nullable();
            $table->date('tanggal_mulai_pengurus')->nullable();
            $table->date('tanggal_akhir_pengurus')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
