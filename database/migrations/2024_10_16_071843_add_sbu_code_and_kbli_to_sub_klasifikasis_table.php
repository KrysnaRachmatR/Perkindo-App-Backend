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
        Schema::table('sub_klasifikasis', function (Blueprint $table) {
            $table->string('sbu_code')->nullable(); // Add this line
            $table->string('kbli')->nullable(); // Add this line
        });
    }

    public function down()
    {
        Schema::table('sub_klasifikasis', function (Blueprint $table) {
            $table->dropColumn(['sbu_code', 'kbli']);
        });
    }
};
