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
        Schema::table('ktas', function (Blueprint $table) {
            $table->boolean('can_reapply')->default(true);
            $table->text('rejection_reason')->nullable();
            $table->date('rejection_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ktas', function (Blueprint $table) {
            $table->dropColumn(['can_reapply', 'rejection_reason', 'rejection_date']);
        });
    }
};
