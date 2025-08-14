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
        Schema::create('profile_contents', function (Blueprint $table) {
            $table->id();
            $table->string('header_image')->nullable(); // path gambar header
            $table->string('title');               // judul utama
            $table->text('section1')->nullable();       // bagian pengantar
            $table->text('visi')->nullable();           // visi organisasi
            $table->text('misi')->nullable();           // misi organisasi
            $table->timestamps();       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_contents');
    }
};
