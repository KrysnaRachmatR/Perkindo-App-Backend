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
        Schema::table('kta', function (Blueprint $table) {
            $table->json('pjbu')->nullable()->change();
            $table->json('data_pengurus_pemegang_saham')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('kta', function (Blueprint $table) {
            $table->text('pjbu')->nullable()->change();
            $table->text('data_pengurus_pemegang_saham')->nullable()->change();
        });
    }    
};
