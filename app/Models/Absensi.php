<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensis';
    public $timestamps = false;
    protected $fillable = [
        'id_rapat',
        'id_user',
        'waktu_absen',
        'signature_path', // hadir / tidak_hadir / izin
    ];

    protected $casts = [
        'waktu_absen' => 'datetime',
    ];

    public function rapat()
    {
        return $this->belongsTo(Rapat::class, 'id_rapat');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
