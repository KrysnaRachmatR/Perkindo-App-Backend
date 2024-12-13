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
        Schema::create('sbus_registration', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('konstruksi_klasifikasi_id');
            $table->unsignedBigInteger('konstruksi_sub_klasifikasi_id');
            $table->string('akta_asosiasi_aktif_masa_berlaku');
            $table->string('akta_perusahaan_pendirian');
            $table->string('akta_perubahan');
            $table->string('pengesahan_menkumham');
            $table->string('nib_berbasis_resiko');
            $table->string('ktp_pengurus');
            $table->string('npwp_pengurus');
            $table->string('SKK');
            $table->string('ijazah_legalisir');
            $table->string('PJTBU');
            $table->string('PJKSBU');
            $table->string('email_perusahaan');
            $table->string('kop_perusahaan');
            $table->string('nomor_hp_penanggung_jawab');
            $table->string('foto_pas_direktur');
            $table->string('surat_pernyataan_penanggung_jawab_mutlak');
            $table->string('surat_pernyataan_SMAP');
            $table->string('lampiran_TKK');
            $table->string('neraca_keuangan_2_tahun_terakhir');
            $table->string('akun_OSS');
            $table->unsignedBigInteger('rekening_id');
            $table->string('bukti_transfer');
            $table->enum('status_diterima', ['approve', 'rejected', 'pending'])->default('pending');
            $table->enum('status_aktif', ['active', 'expired', 'will_expire'])->nullable();
            $table->date('tanggal_diterima')->nullable();
            $table->date('expired_at')->nullable();
            $table->enum('status_perpanjangan_sbus', ['approve', 'rejected', 'pending'])->default('pending');
            $table->text('komentar')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); // Relasi ke users
            $table->foreign('rekening_id')->references('id')->on('rekening_tujuan')->onDelete('cascade');
            $table->foreign('konstruksi_klasifikasi_id')->references('id')->on('klasifikasis')->onDelete('cascade');
            $table->foreign('konstruksi_sub_klasifikasi_id')->references('id')->on('sub_klasifikasis')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbus_registation');
    }
};
