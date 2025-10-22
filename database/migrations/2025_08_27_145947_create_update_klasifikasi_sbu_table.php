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
        Schema::create('klasifikasi', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['konstruksi', 'non_konstruksi']);
            $table->string('kode')->nullable();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('sub_klasifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klasifikasi_id')->constrained('klasifikasi')->cascadeOnDelete();
            $table->string('kode')->nullable();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('sbu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nomor_sbu')->unique();
            $table->enum('jenis', ['konstruksi', 'non_konstruksi']);
            $table->foreignId('klasifikasi_id')->constrained('klasifikasi')->cascadeOnDelete();
            $table->foreignId('sub_klasifikasi_id')->constrained('sub_klasifikasi')->cascadeOnDelete();
            $table->date('tanggal_terbit')->nullable();
            $table->date('tanggal_berlaku_hingga')->nullable();
            $table->enum('status', ['aktif', 'kadaluarsa', 'dicabut'])->default('aktif');
            $table->string('file_sbu')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sbu');
        Schema::dropIfExists('sub_klasifikasi');
        Schema::dropIfExists('klasifikasi');
    }
};
