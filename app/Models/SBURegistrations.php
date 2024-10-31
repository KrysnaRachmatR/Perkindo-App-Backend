<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SbusRegistration extends Model
{
    use HasFactory;

    protected $table = 'sbus_registrations';

    protected $fillable = [
        'akta_asosiasi_aktif_masa_berlaku',
        'akta_perusahaan_pendirian',
        'akta_perubahan',
        'pengesahan_menkumham',
        'nib_berbasis_resiko',
        'ktp_pengurus',
        'npwp_pengurus',
        'skk',
        'ijazah_legalisir',
        'PJTBU',
        'PJKSBU',
        'email_perusahaan',
        'kop_perusahaan',
        'nomor_whatsapp',
        'foto_pas_direktur',
        'surat_pernyataan_tanggung_jawab_mutlak',
        'surat_pernyataan_SMAP',
        'lampiran_tkk',
        'neraca_keuangan_2_tahun_terakhir',
        'akun_oss',
        'klasifikasi_id',
        'status',
        'komentar',
    ];

    // Relasi ke Klasifikasi
    public function klasifikasi()
    {
        return $this->belongsTo(Klasifikasi::class);
    }
}
