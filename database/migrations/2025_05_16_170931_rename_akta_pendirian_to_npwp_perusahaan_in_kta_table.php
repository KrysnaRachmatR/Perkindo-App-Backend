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
            $table->renameColumn('akta_perusahaan', 'npwp_perusahaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kta', function (Blueprint $table) {
            $table->renameColumn('npwp_perusahaan', 'akta_perusahaan');
        });
    }
};
