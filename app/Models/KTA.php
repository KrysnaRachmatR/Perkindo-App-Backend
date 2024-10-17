<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KTA extends Model
{
    use HasFactory;

    // Nama tabel (opsional jika tabel sesuai dengan konvensi Laravel)
    protected $table = 'ktas';

    // Kolom yang dapat diisi (fillable)
    protected $fillable = [
        'formulir_permohonan',
        'pernyataan_kebenaran',
        'pengesahan_menkumham',
        'akta_pendirian',
        'akta_perubahan',
        'npwp_perusahaan',
        'surat_domisili',
        'ktp_pengurus',
        'logo',
        'foto_direktur',
        'npwp_pengurus_akta',
        'bukti_transfer',
        'kabupaten_id',
        'user_id',
        'status_perpanjangan_kta', // Tambahkan status perpanjangan KTA
        'tanggal_diterima', // Tambahkan tanggal diterima
        'komentar', // Tambahkan kolom komentar jika ditolak
    ];

    // Relasi ke model KotaKabupaten
    public function kabupaten()
    {
        return $this->belongsTo(KotaKabupaten::class, 'kabupaten_id');
    }

    // Relasi ke model User (yang membuat KTA)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function isActive()
    {
        return $this->status === 'active' && now()->lessThanOrEqualTo($this->tanggal_diterima->addYear());
    }
    // Method untuk memperpanjang KTA
    public function extendKta($buktiTransfer)
    {
        $this->status_perpanjangan_kta = 'pending'; // Status menjadi pending saat diajukan perpanjangan
        $this->bukti_transfer = $buktiTransfer; // Simpan bukti transfer
        $this->save(); // Simpan perubahan
    }
}
