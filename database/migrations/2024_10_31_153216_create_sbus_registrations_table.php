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
        Schema::create('sbus_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('akta_asosiasi_aktif_masa_berlaku');
            $table->string('akta_perusahaan_pendirian');
            $table->string('akta_perubahan')->nullable();
            $table->string('pengesahan_menkumham');
            $table->string('nib_berbasis_resiko');
            $table->string('ktp_pengurus');
            $table->string('npwp_pengurus');
            $table->string('skk');
            $table->string('ijazah_legalisir');
            $table->string('PJTBU');
            $table->string('PJKSBU');
            $table->string('email_perusahaan');
            $table->string('kop_perusahaan');
            $table->string('nomor_whatsapp');
            $table->string('foto_pas_direktur');
            $table->string('surat_pernyataan_tanggung_jawab_mutlak');
            $table->string('surat_pernyataan_SMAP');
            $table->string('lampiran_tkk');
            $table->string('neraca_keuangan_2_tahun_terakhir');
            $table->string('akun_oss');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbus_registrations');
    }
};
