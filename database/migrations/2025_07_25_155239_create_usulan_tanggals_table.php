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
        Schema::create('usulan_tanggals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_rapat');
            $table->unsignedBigInteger('id_user');
            $table->date('tanggal');
            $table->text('alasan')->nullable();
            $table->timestamps();
            $table->foreign('id_rapat')->references('id')->on('rapats')->onDelete('cascade');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('usulan_tanggals');
    }
};
