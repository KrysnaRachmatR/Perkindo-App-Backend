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
        Schema::table('peserta_rapats', function (Blueprint $table) {
            $table->string('jabatan')->nullable()->after('is_pengurus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peserta_rapats', function (Blueprint $table) {
        $table->dropColumn('jabatan');
        });
    }
};
