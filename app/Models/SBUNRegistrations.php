<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SBUNRegistrations extends Model
{
    use HasFactory;
    protected $table = 'sbun_registrations';

    protected $fillable = [
        'user_id',
        'non_konstruksi_klasifikasi_id',
        'non_konstruksi_sub_klasifikasi_id',
        'akta_pendirian',
        'npwp_perusahaan',
        'ktp_penanggung_jawab',
        'npwp_penanggung_jawab',
        'foto_penanggung_jawab',
        'nomor_hp_penanggung_jawab',
        'ktp_pemegang_saham',
        'npwp_pemegang_saham',
        'email_perusahaan',
        'logo_perusahaan',
        'rekening_id',
        'bukti_transfer',
        'status_aktif',
        'expired_at',
        'approval_status'
    ];

    protected $casts = [
        'approval_status' => 'string',
        'admin_comment' => 'string',
        'expiration_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function non_konstruksi_klasifikasi()
    {
        return $this->belongsTo(NonKonstruksiKlasifikasi::class, 'non_konstruksi_klasifikasi_id');
    }

    public function non_konstruksi_sub_klasifikasi()
    {
        return $this->belongsTo(NonKonstruksiSubKlasifikasi::class, 'non_konstruksi_sub_klasifikasi_id');
    }

    public function rekeningTujuan()
    {
        return $this->belongsTo(RekeningTujuan::class, 'rekening_id');
    }
}
