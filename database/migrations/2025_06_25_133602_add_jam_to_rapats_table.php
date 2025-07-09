<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('rapats', function (Blueprint $table) {
        $table->time('jam')->nullable()->after('tanggal_terpilih');
    });
}

public function down()
{
    Schema::table('rapats', function (Blueprint $table) {
        $table->dropColumn('jam');
    });
}

};
