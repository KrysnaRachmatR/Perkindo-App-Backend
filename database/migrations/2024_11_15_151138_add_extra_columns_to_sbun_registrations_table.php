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
        Schema::table('sbun_registrations', function (Blueprint $table) {
            $table->unsignedBigInteger('rekening_id')->after('logo_perusahaan');
            $table->string('bukti_transfer');
            $table->enum('approval_status', ['pending', 'approved', 'rejected']);
            $table->text('admin_comment')->after('approval_status');
            $table->enum('status_aktif', ['inactive', 'active'])->default('inactive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sbun_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'rekening_id',
                'bukti_transfer',
                'approval_status',
                'admin_comment',
                'status_aktif',
            ]);
        });
    }
};
