<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SbunRegistration extends Model
{
    use HasFactory;

    // Nama tabel yang terkait dengan model ini
    protected $table = 'sbun_registration';

    // Kolom yang bisa diisi (fillable)
    protected $fillable = [
        'user_id',
        'non_konstruksi_klasifikasi_id',
        'non_konstruksi_sub_klasifikasi_id',
        'akta_pendirian',
        'npwp_perusahaan',
        'nib',
        'ktp_penanggung_jawab',
        'nomor_hp_penanggung_jawab',
        'npwp_penanggung_jawab',
        'foto_penanggung_jawab',
        'ktp_pemegang_saham',
        'npwp_pemegang_saham',
        'email_perusahaan',
        'logo_perusahaan',
        'rekening_id',
        'bukti_transfer',
        'status_diterima',
        'status_aktif',
        'tanggal_diterima',
        'expired_at',
        'status_perpanjangan_sbun',
        'komentar',
    ];

    // Relasi dengan model User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi dengan model NonKonstruksiKlasifikasi
    public function nonKonstruksiKlasifikasi()
    {
        return $this->belongsTo(NonKonstruksiKlasifikasi::class, 'non_konstruksi_klasifikasi_id');
    }

    // Relasi dengan model NonKonstruksiSubKlasifikasi
    public function nonKonstruksiSubKlasifikasi()
    {
        return $this->belongsTo(NonKonstruksiSubKlasifikasi::class, 'non_konstruksi_sub_klasifikasi_id');
    }

    // Relasi dengan model RekeningTujuan
    public function rekening()
    {
        return $this->belongsTo(RekeningTujuan::class, 'rekening_id');
    }
}
