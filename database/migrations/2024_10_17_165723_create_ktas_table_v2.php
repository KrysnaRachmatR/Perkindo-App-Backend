<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKtasTableV2 extends Migration
{
    public function up()
    {
        Schema::create('ktas', function (Blueprint $table) {
            $table->id();
            $table->string('formulir_permohonan');
            $table->string('pernyataan_kebenaran');
            $table->string('pengesahan_menkumham');
            $table->string('akta_pendirian')->nullable();
            $table->string('akta_perubahan')->nullable();
            $table->string('npwp_perusahaan');
            $table->string('surat_domisili');
            $table->string('ktp_pengurus');
            $table->string('logo')->nullable();
            $table->string('foto_direktur');
            $table->string('npwp_pengurus_akta');
            $table->string('bukti_transfer');
            $table->foreignId('kabupaten_id')->constrained('kota_kabupaten')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ktas');
    }
}
