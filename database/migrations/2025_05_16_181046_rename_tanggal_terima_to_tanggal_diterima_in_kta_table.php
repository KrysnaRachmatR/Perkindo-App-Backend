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
        Schema::table('kta', function (Blueprint $table) {
            $table->renameColumn('tanggal_terima', 'tanggal_diterima');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kta', function (Blueprint $table) {
            $table->renameColumn('tanggal_diterima', 'tanggal_terima');
        });
    }
};
