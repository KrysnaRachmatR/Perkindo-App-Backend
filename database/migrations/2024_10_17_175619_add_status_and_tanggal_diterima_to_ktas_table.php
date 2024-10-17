<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusAndTanggalDiterimaToKtasTable extends Migration
{
    public function up()
    {
        Schema::table('ktas', function (Blueprint $table) {
            $table->enum('status_perpanjangan_kta', ['active', 'inactive', 'pending', 'rejected'])->default('pending')->after('user_id');
            $table->timestamp('tanggal_diterima')->nullable()->after('status_perpanjangan_kta'); // Perbaikan di sini
        });
    }

    public function down()
    {
        Schema::table('ktas', function (Blueprint $table) {
            $table->dropColumn(['status_perpanjangan_kta', 'tanggal_diterima']);
        });
    }
}
