<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sbus_registrations', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
            $table->text('admin_comment')->nullable()->after('approval_status');
        });
    }

    public function down()
    {
        Schema::table('sbus_registrations', function (Blueprint $table) {
            $table->dropColumn('approval_status');
            $table->dropColumn('admin_comment');
        });
    }
};
