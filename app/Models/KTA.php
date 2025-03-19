<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class KTA extends Model
{
    use HasFactory;

    protected $table = 'kta';

    protected $fillable = [
        'akta_pendirian',
        'npwp_perusahaan',
        'nib',
        'pjbu',
        'data_pengurus_pemegang_saham',
        'alamat_email_badan_usaha',
        'kabupaten_id',
        'rekening_id',
        'bukti_transfer',
        'logo_badan_usaha',
        'user_id',
        'status_diterima',
        'status_aktif',
        'tanggal_diterima',
        'expired_at',
        'status_perpanjangan_kta',
        'komentar',
        'kta_file',
        'can_reapply',
        'rejection_reason',
        'rejection_date',
        'no_kta'
    ];

    protected $casts = [
        'tanggal_diterima' => 'datetime',
        'expired_at' => 'datetime',
        'rejection_date' => 'datetime',
        'can_reapply' => 'boolean',
    ];

    protected $attributes = [
        'status_perpanjangan_kta' => 'pending',
        'status_diterima' => 'pending',
        'status_aktif' => 'will_expire',
        'can_reapply' => false,
    ];

    // Relasi ke model KotaKabupaten (Kabupaten)
    public function kabupaten()
    {
        return $this->belongsTo(KotaKabupaten::class, 'kabupaten_id');
    }

    // Relasi ke model User
    public function user()
    {
        return $this->belongsTo(User::class)->select('id');
    }
    // Relasi ke model Rekening
    public function rekening()
    {
        return $this->belongsTo(RekeningTujuan::class, 'rekening_id');
    }

    // Mengecek apakah KTA masih aktif
    public function isActive()
    {
        return $this->expired_at && Carbon::now()->lessThanOrEqualTo($this->expired_at);
    }

    // Memperpanjang KTA
    public function extendKta($buktiTransfer)
    {
        $this->update([
            'status_perpanjangan_kta' => 'pending',
            'bukti_transfer' => $buktiTransfer,
        ]);
    }

    // Menerima KTA
    public function acceptKta()
    {
        $this->update([
            'status_diterima' => 'approve',
            'tanggal_diterima' => Carbon::now(),
            'status_aktif' => 'active',
            'expired_at' => Carbon::now()->addYear(),
        ]);
    }

    // Menolak KTA dengan komentar
    public function rejectKta($komentar)
    {
        $this->update([
            'status_diterima' => 'rejected',
            'komentar' => $komentar,
        ]);
    }

    // Mengatur status KTA menjadi expired jika sudah melewati tanggal expired
    public function checkExpiredStatus()
    {
        if ($this->expired_at && Carbon::now()->greaterThan($this->expired_at)) {
            $this->update(['status_aktif' => 'expired']);
        }
    }

    // Custom accessor untuk status KTA
    public function getStatusLabelAttribute()
    {
        return [
            'approve' => 'Diterima',
            'rejected' => 'Ditolak',
            'pending' => 'Menunggu Persetujuan',
        ][$this->status_diterima] ?? 'Status Tidak Diketahui';
    }

    // Custom accessor untuk status perpanjangan KTA
    public function getStatusPerpanjanganLabelAttribute()
    {
        return [
            'pending' => 'Menunggu Persetujuan',
            'approve' => 'Disetujui',
            'rejected' => 'Ditolak',
        ][$this->status_perpanjangan_kta] ?? 'Status Tidak Diketahui';
    }

    // Custom accessor untuk status keberlanjutan KTA
    public function getStatusAktifLabelAttribute()
    {
        return [
            'active' => 'Aktif',
            'expired' => 'Kedaluwarsa',
            'will_expire' => 'Akan Kedaluwarsa',
        ][$this->status_aktif] ?? 'Status Tidak Diketahui';
    }
}
