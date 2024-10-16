<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNonKonstruksiSubKlasifikasisTable extends Migration
{
    public function up()
    {
        Schema::create('non_konstruksi_sub_klasifikasis', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // Nama sub klasifikasi
            $table->string('sbu_code'); // Kode SBU
            $table->foreignId('klasifikasi_id')->constrained('non_konstruksi_klasifikasis'); // Relasi ke klasifikasi
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('non_konstruksi_sub_klasifikasis');
    }
}
