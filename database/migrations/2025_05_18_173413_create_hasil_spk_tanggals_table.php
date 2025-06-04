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
        Schema::create('hasil_spk_tanggals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rapat_id')->constrained('rapats')->onDelete('cascade');
            $table->date('tanggal_terpilih');
            $table->json('data_perhitungan')->nullable(); // Simpan detail nilai/spk jika ingin
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_spk_tanggals');
    }
};
