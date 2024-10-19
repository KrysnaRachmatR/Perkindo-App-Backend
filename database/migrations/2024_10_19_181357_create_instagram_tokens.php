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
        Schema::create('instagram_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token');
            $table->string('token_type')->default('Bearer'); // Jika ingin memberikan default
            $table->integer('expires_in'); // Mengubah menjadi integer
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_tokens');
    }
};
