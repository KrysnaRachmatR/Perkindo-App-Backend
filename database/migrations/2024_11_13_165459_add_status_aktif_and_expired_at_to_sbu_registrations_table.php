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
            $table->enum('status_aktif', ['inactive', 'active'])->default('inactive');
            $table->timestamp('expired_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sbus_registrations', function (Blueprint $table) {});
    }
};
