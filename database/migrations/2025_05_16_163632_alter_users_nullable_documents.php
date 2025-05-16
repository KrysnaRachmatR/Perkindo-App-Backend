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
    Schema::table('users', function (Blueprint $table) {
        $table->string('logo_perusahaan')->nullable()->change();
        $table->string('ktp_penanggung_jawab')->nullable()->change();
        $table->string('npwp_penanggung_jawab')->nullable()->change();
        $table->string('ktp_pemegang_saham')->nullable()->change();
        $table->string('npwp_pemegang_saham')->nullable()->change();
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('logo_perusahaan')->nullable(false)->change();
        $table->string('ktp_penanggung_jawab')->nullable(false)->change();
        $table->string('npwp_penanggung_jawab')->nullable(false)->change();
        $table->string('ktp_pemegang_saham')->nullable(false)->change();
        $table->string('npwp_pemegang_saham')->nullable(false)->change();
    });
}

};
