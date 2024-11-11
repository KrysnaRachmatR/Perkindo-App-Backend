<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'rekening_id',
        'status', // Status pendaftaran KTA, contoh: 'accepted', 'rejected', 'pending'
        'status_perpanjangan_kta', // Status perpanjangan KTA, contoh: 'pending', 'approved'
        'tanggal_diterima', // Tanggal KTA diterima
        'komentar', // Komentar jika perpanjangan ditolak
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

    // Method untuk memeriksa apakah KTA masih aktif atau sudah tidak aktif berdasarkan tanggal diterima
    public function isActive()
    {
        // Jika belum diterima, dianggap tidak aktif
        if (!$this->tanggal_diterima) {
            return false;
        }
        // Menghitung apakah tanggal diterima lebih dari 1 tahun yang lalu
        return now()->lessThanOrEqualTo(Carbon::parse($this->tanggal_diterima)->addYear());
    }

    // Method untuk memperpanjang KTA
    public function extendKta($buktiTransfer)
    {
        $this->status_perpanjangan_kta = 'pending'; // Status menjadi pending saat diajukan perpanjangan
        $this->bukti_transfer = $buktiTransfer; // Simpan bukti transfer
        $this->save(); // Simpan perubahan
    }

    // Method untuk mengatur KTA sebagai diterima (accepted) dan menetapkan tanggal diterima
    public function acceptKta()
    {
        $this->status = 'accepted'; // Status KTA diterima
        $this->tanggal_diterima = now(); // Set tanggal diterima saat KTA diaktifkan
        $this->save();
    }

    // Method untuk menolak KTA dengan komentar
    public function rejectKta($komentar)
    {
        $this->status = 'rejected'; // Status KTA ditolak
        $this->komentar = $komentar; // Simpan komentar penolakan
        $this->save();
    }

    // Relasi ke model RekeningTujuan
    public function rekeningTujuan()
    {
        return $this->belongsTo(RekeningTujuan::class, 'rekening_id');
    }

    // Menambahkan custom accessor untuk status KTA yang lebih mudah diakses
    public function getStatusLabelAttribute()
    {
        $labels = [
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak',
            'pending' => 'Menunggu Persetujuan',
        ];

        return $labels[$this->status] ?? 'Status Tidak Diketahui';
    }

    // Menambahkan custom accessor untuk status perpanjangan KTA
    public function getStatusPerpanjanganLabelAttribute()
    {
        $labels = [
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        return $labels[$this->status_perpanjangan_kta] ?? 'Status Tidak Diketahui';
    }
}
