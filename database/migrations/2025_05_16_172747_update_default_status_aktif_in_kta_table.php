<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kta', function (Blueprint $table) {
            $table->dropColumn('status_aktif');
        });

        Schema::table('kta', function (Blueprint $table) {
            $table->enum('status_aktif', ['active', 'expired', 'will_expire'])->default('will_expire')->after('status_diterima');
        });
    }

    public function down(): void
    {
        Schema::table('kta', function (Blueprint $table) {
            $table->dropColumn('status_aktif');
        });

        Schema::table('kta', function (Blueprint $table) {
            $table->enum('status_aktif', ['active', 'expired'])->default('active')->after('status_diterima');
        });
    }
};
