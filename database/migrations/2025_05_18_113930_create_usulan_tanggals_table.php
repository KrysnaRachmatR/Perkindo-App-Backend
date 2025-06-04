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
        Schema::create('usulan_tanggals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rapat_id')->constrained('rapats')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('tanggal_usulan')->nullable();
            $table->text('catatan_opsional')->nullable();
            $table->enum('status_usulan', ['dipertimbangkan', 'diterima', 'ditolak'])->default('dipertimbangkan');
            $table->boolean('alasan_sistem')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usulan_tanggals');
    }
};
