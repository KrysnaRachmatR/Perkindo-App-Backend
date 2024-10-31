<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusAndKomentarToSbusRegistrationsTable extends Migration
{
    public function up()
    {
        Schema::table('sbus_registrations', function (Blueprint $table) {
            $table->string('status')->default('pending'); // Status: pending, approved, rejected
            $table->text('komentar')->nullable(); // Komentar dari admin
        });
    }

    public function down()
    {
        Schema::table('sbus_registrations', function (Blueprint $table) {
            $table->dropColumn(['status', 'komentar']);
        });
    }
}
