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
        Schema::create('notulensis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_rapat');
            $table->unsignedBigInteger('dibuat_oleh');
            $table->text('isi');
            $table->enum('status', ['menunggu_approval', 'disetujui', 'ditolak', 'auto_disetujui'])->default('menunggu_approval');
            $table->string('file_pdf_path')->nullable();
            $table->timestamps();
            $table->timestamp('approved_at')->nullable();
            $table->foreign('id_rapat')->references('id')->on('rapats')->onDelete('cascade');
            $table->foreign('dibuat_oleh')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('notulensis');
    }
};
