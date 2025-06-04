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
        Schema::create('rapats', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('agenda');
            $table->string('lokasi');
            $table->enum('urgensi', ['rutin', 'mendesak', 'kritis'])->default('rutin');
            $table->date('tanggal_polling_berakhir')->nullable();
            $table->date('tanggal_terpilih')->nullable();
            $table->string('file_undangan_pdf')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['draft', 'polling', 'finalisasi', 'selesai'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rapats');
    }
};
