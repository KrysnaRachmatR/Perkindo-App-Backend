<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('log_notifikasis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_rapat')->nullable();
            $table->unsignedBigInteger('id_user')->nullable();
            $table->enum('jenis', ['email', 'whatsapp']);
            $table->text('isi');
            $table->enum('status_pengiriman', ['berhasil', 'gagal'])->default('berhasil');
            $table->timestamp('dikirim_pada')->nullable();
            $table->timestamps();
            $table->foreign('id_rapat')->references('id')->on('rapats')->onDelete('set null');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::dropIfExists('log_notifikasis');
    }
};