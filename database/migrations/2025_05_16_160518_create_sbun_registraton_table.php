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

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('non_konstruksi_klasifikasi_id')->constrained('non_konstruksi_klasifikasis')->onDelete('cascade');
            $table->foreignId('non_konstruksi_sub_klasifikasi_id')->constrained('non_konstruksi_sub_klasifikasis')->onDelete('cascade');

            $table->string('akta_pendirian')->nullable();
            $table->string('npwp_perusahaan')->nullable();
            $table->string('nib')->nullable();
            $table->string('ktp_penanggung_jawab')->nullable();
            $table->string('nomor_hp_penanggung_jawab')->nullable();
            $table->string('npwp_penanggung_jawab')->nullable();
            $table->string('foto_penanggung_jawab')->nullable();
            $table->string('ktp_pemegang_saham')->nullable();
            $table->string('npwp_pemegang_saham')->nullable();
            $table->string('email_perusahaan')->nullable();
            $table->string('logo_perusahaan')->nullable();

            $table->foreignId('rekening_id')->nullable()->constrained('rekening_tujuan')->nullOnDelete();

            $table->string('bukti_transfer')->nullable();
            $table->enum('status_diterima', ['pending', 'approve', 'rejected'])->default('pending');
            $table->enum('status_aktif', ['active', 'will_expire', 'expired'])->default('will_expire');
            $table->timestamp('tanggal_diterima')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->enum('status_perpanjangan_sbun', ['pending', 'approve', 'rejected'])->default('pending');
            $table->text('komentar')->nullable();

            $table->timestamps();
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
