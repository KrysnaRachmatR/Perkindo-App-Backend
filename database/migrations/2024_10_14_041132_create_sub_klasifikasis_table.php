<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubKlasifikasisTable extends Migration
{
    public function up()
    {
        Schema::create('sub_klasifikasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klasifikasi_id')->constrained('klasifikasis')->onDelete('cascade');
            $table->string('nama_sub_klasifikasi');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_klasifikasis');
    }
}
