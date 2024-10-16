<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNonKonstruksiKlasifikasisTable extends Migration
{
    public function up()
    {
        Schema::create('non_konstruksi_klasifikasis', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // Nama klasifikasi
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('non_konstruksi_klasifikasis');
    }
}
