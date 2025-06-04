<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('jabatan', [
                'ketua',
                'wakil_ketua_1',
                'wakil_ketua_2',
                'wakil_ketua_3',
                'wakil_ketua_4',
                'wakil_ketua_5',
                'ketua_sekretaris',
                'wakil_sekretaris_1',
                'ketua_bendahara',
                'wakil_bendahara_1',
                'wakil_bendahara_2',
            ])->nullable()->after('is_pengurus');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('jabatan');
        });
    }
};
