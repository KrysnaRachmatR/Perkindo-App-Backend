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
        Schema::dropIfExists('vote_tanggals');
        Schema::dropIfExists('polling_tanggals');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
