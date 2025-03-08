<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kta', function (Blueprint $table) {
            $table->unsignedBigInteger('kabupaten_id')->nullable()->after('alamat_email_badan_usaha');
            $table->unsignedBigInteger('rekening_id')->nullable()->after('kabupaten_id');
            $table->string('bukti_transfer')->nullable()->after('rekening_id');

            // Tambahkan foreign key jika diperlukan
            $table->foreign('kabupaten_id')->references('id')->on('kota_kabupaten')->onDelete('set null');
            $table->foreign('rekening_id')->references('id')->on('rekening_tujuan')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kta', function (Blueprint $table) {
            $table->dropForeign(['kabupaten_id']);
            $table->dropForeign(['rekening_id']);
            $table->dropColumn(['kabupaten_id', 'rekening_id', 'bukti_transfer']);
        });
    }
};
