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
        Schema::create('sbu_codes', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('sub_klasifikasi_id')->constrained('sub_klasifikasis')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbu_codes');
    }
};
