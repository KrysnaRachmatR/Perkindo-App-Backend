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
        Schema::table('sbus_registrations', function (Blueprint $table) {
            $table->foreignId('klasifikasi_id')->constrained('klasifikasis')->onDelete('cascade')->after('email_perusahaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sbus_registrations', function (Blueprint $table) {
            $table->dropForeign(['klasifikasi_id']);
            $table->dropColumn('klasifikasi_id');
        });
    }
};
