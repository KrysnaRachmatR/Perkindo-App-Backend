<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('usulan_tanggals');
        Schema::dropIfExists('hasil_spk_tanggals');
    }

    public function down(): void
    {
        Schema::create('usulan_tanggals', function (Blueprint $table) {
            $table->id();
            // Sesuaikan struktur kolom jika ingin bisa rollback
            $table->unsignedBigInteger('rapat_id');
            $table->date('tanggal');
            $table->timestamps();
        });

        Schema::create('hasil_spk_tanggals', function (Blueprint $table) {
            $table->id();
            // Sesuaikan struktur kolom jika ingin bisa rollback
            $table->unsignedBigInteger('rapat_id');
            $table->date('tanggal');
            $table->decimal('nilai', 8, 2)->nullable();
            $table->timestamps();
        });
    }
};
