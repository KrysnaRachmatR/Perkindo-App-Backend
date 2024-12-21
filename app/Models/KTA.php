<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class KTA extends Model
{
    use HasFactory;

    protected $table = 'ktas';

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
        'rekening_id',
        'status_diterima',
        'status_aktif',
        'tanggal_diterima',
        'expired_at',
        'status_perpanjangan_kta',
        'komentar',
        'kta_file',
        'can_reapply',
        'rejection_reason',
        'rejection_date'
    ];

    protected $cast = [
        'can_reapply' => 'boolean',
        'rejection_date' => 'date'
    ];

    // Relasi ke model KotaKabupaten
    public function kabupaten()
    {
        return $this->belongsTo(KotaKabupaten::class, 'kabupaten_id');
    }

    // Relasi ke model User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Memeriksa apakah KTA masih aktif
    public function isActive()
    {
        if (!$this->tanggal_diterima) {
            return false;
        }

        // Jika tanggal kedaluwarsa lebih dari sekarang
        return now()->lessThanOrEqualTo(Carbon::parse($this->expired_at));
    }

    // Memperpanjang KTA
    public function extendKta($buktiTransfer)
    {
        $this->status_perpanjangan_kta = 'pending';
        $this->bukti_transfer = $buktiTransfer;
        $this->save();
    }

    // Menerima KTA dan mengatur tanggal diterima
    public function acceptKta()
    {
        $this->status_diterima = 'approve';
        $this->tanggal_diterima = now();
        $this->status_aktif = 'active';
        $this->expired_at = now()->addYear();
        $this->save();
    }

    // Menolak KTA dengan komentar
    public function rejectKta($komentar)
    {
        $this->status_diterima = 'rejected';
        $this->komentar = $komentar;
        $this->save();
    }

    // Mengatur KTA sebagai expired jika sudah melewati tanggal expired
    public function checkExpiredStatus()
    {
        if ($this->expired_at && now()->greaterThan($this->expired_at)) {
            $this->status_aktif = 'expired';
            $this->save();
        }
    }

    // Relasi ke model RekeningTujuan
    public function rekeningTujuan()
    {
        return $this->belongsTo(RekeningTujuan::class, 'rekening_id');
    }

    // Custom accessor untuk status KTA
    public function getStatusLabelAttribute()
    {
        $labels = [
            'approve' => 'Diterima',
            'rejected' => 'Ditolak',
            'pending' => 'Menunggu Persetujuan',
        ];

        return $labels[$this->status_diterima] ?? 'Status Tidak Diketahui';
    }

    // Custom accessor untuk status perpanjangan KTA
    public function getStatusPerpanjanganLabelAttribute()
    {
        $labels = [
            'pending' => 'Menunggu Persetujuan',
            'approve' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        return $labels[$this->status_perpanjangan_kta] ?? 'Status Tidak Diketahui';
    }

    // Custom accessor untuk status keberlanjutan KTA
    public function getStatusAktifLabelAttribute()
    {
        $labels = [
            'active' => 'Aktif',
            'expired' => 'Kedaluwarsa',
            'will_expire' => 'Akan Kadaluarsa',
        ];

        return $labels[$this->status_aktif] ?? 'Status Tidak Diketahui';
    }
}
