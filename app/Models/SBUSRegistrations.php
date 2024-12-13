<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SBUSRegistrations extends Model
{
    use HasFactory;

    protected $table = 'sbus_registration';

    protected $fillable = [
        'user_id',
        'konstruksi_klasifikasi_id',
        'konstruksi_sub_klasifikasi_id',
        'akta_asosiasi_aktif_masa_berlaku',
        'akta_perusahaan_pendirian',
        'akta_perubahan',
        'pengesahan_menkumham',
        'nib_berbasis_resiko',
        'ktp_pengurus',
        'npwp_pengurus',
        'SKK',
        'ijazah_legalisir',
        'PJTBU',
        'PJKSBU',
        'email_perusahaan',
        'kop_perusahaan',
        'nomor_hp_penanggung_jawab',
        'foto_pas_direktur',
        'surat_pernyataan_penanggung_jawab_mutlak',
        'surat_pernyataan_SMAP',
        'lampiran_TKK',
        'neraca_keuangan_2_tahun_terakhir',
        'akun_OSS',
        'rekening_id',
        'bukti_transfer',
        'status_diterima',
        'status_aktif',
        'tanggal_diterima',
        'expired_at',
        'status_perpanjangan_sbus',
        'komentar',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'tanggal_diterima' => 'datetime',
        'expired_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relasi dengan model User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi dengan model KonstruksiKlasifikasi
    public function konstruksiKlasifikasi()
    {
        return $this->belongsTo(Klasifikasi::class, 'konstruksi_klasifikasi_id');
    }

    public function konstruksiSubKlasifikasi()
    {
        return $this->belongsTo(SubKlasifikasi::class, 'konstruksi_sub_klasifikasi_id');
    }

    // Relasi dengan model RekeningTujuan
    public function rekening()
    {
        return $this->belongsTo(RekeningTujuan::class, 'rekening_id');
    }
}
