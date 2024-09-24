<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sbu_non_konstruksi', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->string('nama_badan_usaha');
            $table->string('alamat');
            $table->string('direktur');
            $table->string('kode_sbu');
            $table->string('tanggal_masa_berlaku');
            $table->string('sampai_dengan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbu_non_konstruksi');
    }
};
