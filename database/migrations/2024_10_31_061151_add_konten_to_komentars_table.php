<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKontenToKomentarsTable extends Migration
{
    public function up()
    {
        Schema::table('komentars', function (Blueprint $table) {
            $table->text('konten')->after('nama'); // tambahkan kolom konten setelah nama
        });
    }

    public function down()
    {
        Schema::table('komentars', function (Blueprint $table) {
            $table->dropColumn('konten');
        });
    }
}
