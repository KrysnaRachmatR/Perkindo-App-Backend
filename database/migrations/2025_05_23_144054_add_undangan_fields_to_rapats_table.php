<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUndanganFieldsToRapatsTable extends Migration
{
    public function up()
    {
        Schema::table('rapats', function (Blueprint $table) {
            $table->string('nomor')->nullable();
            $table->string('lampiran')->nullable();
            $table->string('hal')->nullable();
            $table->json('topik')->nullable(); // Menyimpan array topik dalam format JSON
            $table->string('header_image')->nullable(); // path gambar header
            $table->string('tanda_tangan_image')->nullable(); // path gambar tanda tangan
        });
    }

    public function down()
    {
        Schema::table('rapats', function (Blueprint $table) {
            $table->dropColumn([
                'nomor',
                'lampiran',
                'hal',
                'topik',
                'header_image',
                'tanda_tangan_image',
            ]);
        });
    }
}
