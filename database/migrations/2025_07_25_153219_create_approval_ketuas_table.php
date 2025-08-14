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
        Schema::create('approval_ketuas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_rapat');
            $table->unsignedBigInteger('id_ketua');

            $table->enum('status', ['approved', 'rejected'])->default('approved');
            $table->text('catatan')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->foreign('id_rapat')->references('id')->on('rapats')->onDelete('cascade');
            $table->foreign('id_ketua')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_ketuas');
    }
};
