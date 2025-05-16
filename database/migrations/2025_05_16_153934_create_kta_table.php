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
        Schema::create('kta', function (Blueprint $table) {
            $table->id();

            $table->string('akta_pendirian');
            $table->string('akta_perusahaan');
            $table->string('nib');

            $table->string('ktp_penanggung_jawab');
            $table->string('npwp_penanggung_jawab');
            $table->string('no_hp_penanggung_jawab');
            $table->string('foto_penanggung_jawab');

            $table->string('ktp_pengurus');
            $table->string('npwp_pengurus');

            $table->string('email_perusahaan')->nullable();
            $table->unsignedBigInteger('kabupaten_id')->nullable();
            $table->unsignedBigInteger('rekening_id')->nullable();

            $table->integer('durasi_tahun');
            $table->string('bukti_transfer')->nullable();
            $table->string('logo_perusahaan')->nullable();

            $table->unsignedBigInteger('user_id');

            $table->enum('status_diterima', ['pending', 'approve', 'rejected'])->default('pending');
            $table->enum('status_aktif', ['active', 'expired', 'will_expired'])->default('active');
            $table->date('expired_at')->nullable();
            $table->date('tanggal_terima')->nullable();

            $table->enum('status_perpanjangan_kta', ['pending', 'approve', 'rejected'])->default('pending');
            $table->string('komentar')->nullable();
            $table->string('kta_file')->nullable();
            
            $table->boolean('can_reapply')->default(false);
            $table->string('rejection_date')->nullable();
            $table->string('rejection_reason')->nullable();

            $table->string('no_kta')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('kabupaten_id')->references('id')->on('kota_kabupaten')->onDelete('set null');
            $table->foreign('rekening_id')->references('id')->on('rekening_tujuan')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kta');
    }
};
