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
    Schema::create('non_konstruksi_sub_klasifikasis', function (Blueprint $table) {
        $table->id();
        $table->string('nama');
        $table->unsignedBigInteger('non_konstruksi_klasifikasi_id');

        // foreign key dengan nama pendek
        $table->foreign('non_konstruksi_klasifikasi_id', 'fk_sub_to_klasifikasi')
              ->references('id')
              ->on('non_konstruksi_klasifikasis')
              ->onDelete('cascade');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_konstruksi_sub_klasifikasis');
    }
};
