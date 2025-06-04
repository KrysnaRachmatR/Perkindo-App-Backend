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
        Schema::table('rapats', function (Blueprint $table) {
            // Hapus foreign key lama ke users
            $table->dropForeign(['created_by']);

            // Ubah relasi menjadi ke admins
            $table->foreign('created_by')
                  ->references('id')->on('admins')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('rapats', function (Blueprint $table) {
            // Hapus foreign key ke admins
            $table->dropForeign(['created_by']);

            // Kembalikan ke users jika di-rollback
            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }
};
