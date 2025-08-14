<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_rapat');
            $table->unsignedBigInteger('id_user');
            $table->timestamp('waktu_absen')->nullable();
            $table->string('signature_path')->nullable();
            $table->foreign('id_rapat')->references('id')->on('rapats')->onDelete('cascade');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('absensis');
    }
};
