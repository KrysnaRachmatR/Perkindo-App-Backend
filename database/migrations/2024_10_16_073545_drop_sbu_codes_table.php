<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropSbuCodesTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('sbu_codes');
    }

    public function down() {}
}
