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
        Schema::create('ktas', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->unsignedBigInteger('user_id'); // Relasi ke tabel users
            $table->string('logo'); // Path logo perusahaan
            $table->string('foto_direktur'); // Path foto direktur
            $table->string('formulir_permohonan'); // Path file formulir permohonan
            $table->string('pernyataan_kebenaran'); // Path file pernyataan kebenaran
            $table->string('pengesahan_menkumham'); // Path file pengesahan dari Menkumham
            $table->string('akta_pendirian'); // Path file akta pendirian
            $table->string('akta_perubahan'); // Path file akta perubahan
            $table->string('npwp_perusahaan'); // Path file NPWP perusahaan
            $table->string('surat_domisili'); // Path file surat domisili perusahaan
            $table->string('ktp_pengurus'); // Path file KTP pengurus
            $table->string('npwp_pengurus_akta'); // Path file NPWP pengurus yang tertera di akta
            $table->string('bukti_transfer'); // Path file bukti transfer pembayaran
            $table->unsignedBigInteger('rekening_id'); // Relasi ke tabel rekening_tujuan
            $table->unsignedBigInteger('kabupaten_id'); // Relasi ke tabel kota_kabupaten
            $table->enum('status_diterima', ['approve', 'rejected', 'pending'])->default('pending'); // Status KTA
            $table->enum('status_aktif', ['active', 'expired', 'will_expire'])->nullable(); // Status keberlanjutan KTA
            $table->date('tanggal_diterima')->nullable(); // Tanggal KTA diterima
            $table->date('expired_at')->nullable(); // Tanggal kedaluwarsa KTA
            $table->enum('status_perpanjangan_kta', ['approve', 'rejected', 'pending'])->default('pending'); // Status perpanjangan KTA
            $table->text('komentar')->nullable(); // Komentar jika ditolak
            $table->string('kta_file')->nullable(); // Path file KTA
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); // Relasi ke users
            $table->foreign('rekening_id')->references('id')->on('rekening_tujuan')->onDelete('cascade'); // Relasi ke rekening_tujuan
            $table->foreign('kabupaten_id')->references('id')->on('kota_kabupaten')->onDelete('cascade'); // Relasi ke kota_kabupaten
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ktas');
    }
};
