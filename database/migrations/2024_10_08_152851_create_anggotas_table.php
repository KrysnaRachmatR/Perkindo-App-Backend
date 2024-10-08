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
        Schema::create('anggota', function (Blueprint $table) {
            $table->id();
            $table->string('nama_badan_usaha');
            $table->string('alamat');
            $table->string('direktur');
            $table->string('kode_sbu');
            $table->date('tanggal_masa_berlaku');
            $table->date('sampai_dengan');
            $table->enum('jenis_sbu', ['konstruksi', 'non-konstruksi']);
            $table->boolean('status_aktif')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};
