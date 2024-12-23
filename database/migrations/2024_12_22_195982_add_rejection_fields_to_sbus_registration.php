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
        Schema::table('sbus_registration', function (Blueprint $table) {
            $table->timestamp('rejection_date')->nullable();
            $table->boolean('can_reapply')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sbus_registration', function (Blueprint $table) {
            $table->dropColumn(['rejection_date', 'can_reapply']);
        });
    }
};
