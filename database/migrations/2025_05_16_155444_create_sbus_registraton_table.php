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

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('konstruksi_klasifikasi_id')->constrained('klasifikasis')->onDelete('cascade');
            $table->foreignId('konstruksi_sub_klasifikasi_id')->constrained('sub_klasifikasis')->onDelete('cascade');

            $table->string('akta_asosiasi_aktif_masa_berlaku')->nullable();
            $table->string('akta_perusahaan_pendirian')->nullable();
            $table->string('akta_perubahan')->nullable();
            $table->string('pengesahan_menkumham')->nullable();
            $table->string('npwp_perusahaan')->nullable();
            $table->string('nib_berbasis_resiko')->nullable();
            $table->string('ktp_pengurus')->nullable();
            $table->string('npwp_pengurus')->nullable();
            $table->string('PJTBU')->nullable();
            $table->string('PJKSBU')->nullable();
            $table->string('email_perusahaan')->nullable();
            $table->string('kop_perusahaan')->nullable();
            $table->string('no_hp_direktur')->nullable();
            $table->string('foto_pas_direktur')->nullable();
            $table->string('surat_pernyataan_penanggung_jawab_mutlak')->nullable();
            $table->string('surat_pernyataan_SMAP')->nullable();
            $table->string('lampiran_TKK')->nullable();
            $table->string('neraca_keuangan_2_tahun_terakhir')->nullable();
            $table->string('akun_OSS')->nullable();

            $table->foreignId('rekening_id')->nullable()->constrained('rekening_tujuan')->nullOnDelete();

            $table->string('bukti_transfer')->nullable();
            $table->enum('status_diterima', ['pending', 'approve', 'rejected'])->default('pending');
            $table->enum('status_aktif', ['active', 'will_expire', 'expired'])->default('will_expire');
            $table->timestamp('tanggal_diterima')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->enum('status_perpanjangan_sbus', ['pending', 'approve', 'rejected'])->default('pending');
            $table->text('komentar')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbus_registration');
    }
};
