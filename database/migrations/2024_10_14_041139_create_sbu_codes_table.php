<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSbuCodesTable extends Migration
{
    public function up()
    {
        Schema::create('sbu_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_klasifikasi_id')->constrained('sub_klasifikasis')->onDelete('cascade');
            $table->string('kode_sbu')->unique();
            $table->string('kbli');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sbu_codes');
    }
}
