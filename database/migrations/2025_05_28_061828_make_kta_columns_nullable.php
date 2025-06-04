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
            $table->string('akta_pendirian')->nullable()->change();
            $table->string('npwp_perusahaan')->nullable()->change();
            $table->string('nib')->nullable()->change();
            $table->string('bukti_transfer')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kta', function (Blueprint $table) {
            //
        });
    }
};
