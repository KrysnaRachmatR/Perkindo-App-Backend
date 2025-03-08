<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('akta_pendirian');
            $table->string('npwp_perusahaan');
            $table->string('nib'); 
            $table->string('pjbu');
            $table->string('data_pengurus_pemegang_saham');
            $table->string('alamat_email_badan_usaha');
            $table->string('logo_badan_usaha');
            $table->enum('status_diterima', ['approve', 'rejected', 'pending'])->default('pending');
            $table->enum('status_aktif', ['active', 'expired', 'will_expire'])->default('active');
            $table->date('tanggal_diterima')->nullable();
            $table->date('expired_at')->nullable();
            $table->enum('status_perpanjangan_kta', ['approve', 'rejected', 'pending'])->default('pending');
            $table->text('komentar')->nullable();
            $table->string('kta_file')->nullable();
            $table->boolean('can_reapply')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->date('rejection_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kta');
    }
};
