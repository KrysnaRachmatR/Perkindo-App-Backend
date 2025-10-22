<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sbu extends Model
{
    use HasFactory;

    protected $table = 'sbu';

    protected $fillable = [
        'user_id',
        'jenis',
        'klasifikasi_id',
        'nomor_sbu',
        'sub_klasifikasi_id',
        'tanggal_terbit',
        'tanggal_berlaku_hingga',
        'status',
        'file_sbu',
    ];

    // Relasi: SBU milik 1 User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi: SBU milik 1 Klasifikasi
    public function klasifikasi()
    {
        return $this->belongsTo(Klasifikasi::class, 'klasifikasi_id');
    }

    // Relasi: SBU milik 1 SubKlasifikasi
    public function subKlasifikasi()
    {
        return $this->belongsTo(SubKlasifikasi::class, 'sub_klasifikasi_id');
    }
}
