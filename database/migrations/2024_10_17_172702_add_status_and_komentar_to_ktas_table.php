<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusAndKomentarToKtasTable extends Migration
{
    public function up()
    {
        Schema::table('ktas', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending')->after('user_id'); // Menambahkan kolom status
            $table->text('komentar')->nullable()->after('status'); // Menambahkan kolom komentar
        });
    }

    public function down()
    {
        Schema::table('ktas', function (Blueprint $table) {
            $table->dropColumn(['status', 'komentar']); // Menghapus kolom jika rollback
        });
    }
}
