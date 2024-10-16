<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveKbliFromSubKlasifikasisTable extends Migration
{
    public function up()
    {
        Schema::table('sub_klasifikasis', function (Blueprint $table) {
            $table->dropColumn('kbli');
        });
    }

    public function down()
    {
        Schema::table('sub_klasifikasis', function (Blueprint $table) {
            $table->string('kbli')->nullable();
        });
    }
}
