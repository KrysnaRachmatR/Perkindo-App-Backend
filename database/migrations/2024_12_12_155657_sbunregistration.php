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
        Schema::create('sbun_registration', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('non_konstruksi_klasifikasi_id');
            $table->unsignedBigInteger('non_konstruksi_sub_klasifikasi_id');
            $table->string('akta_pendirian');
            $table->string('npwp_perusahaan');
            $table->string('ktp_penanggung_jawab');
            $table->string('nomor_hp_penanggung_jawab');
            $table->string('ktp_pemegang_saham');
            $table->string('npwp_pemegang_saham');
            $table->string('email_perusahaan');
            $table->string('logo_perusahaan');
            $table->unsignedBigInteger('rekening_id');
            $table->string('bukti_transfer');
            $table->enum('status_diterima', ['approve', 'rejected', 'pending'])->default('pending');
            $table->enum('status_aktif', ['active', 'expired', 'will_expire'])->nullable(); // Status keberlanjutan KTA
            $table->date('tanggal_diterima')->nullable();
            $table->date('expired_at')->nullable();
            $table->enum('status_perpanjangan_sbun', ['approve', 'rejected', 'pending'])->default('pending');
            $table->text('komentar')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); // Relasi ke users
            $table->foreign('rekening_id')->references('id')->on('rekening_tujuan')->onDelete('cascade');
            $table->foreign('non_konstruksi_klasifikasi_id')->references('id')->on('non_konstruksi_klasifikasis')->onDelete('cascade');
            $table->foreign('non_konstruksi_sub_klasifikasi_id')->references('id')->on('non_konstruksi_sub_klasifikasis')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbun_registration');
    }
};
