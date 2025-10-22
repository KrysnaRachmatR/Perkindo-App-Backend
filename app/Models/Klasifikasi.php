<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Klasifikasi extends Model
{
    use HasFactory;

    protected $table = 'klasifikasi';

    protected $fillable = [
        'jenis',
        'kode',
        'nama',
        'deskripsi',
    ];

    // Relasi: Klasifikasi punya banyak SubKlasifikasi
    public function subKlasifikasis()
    {
        return $this->hasMany(SubKlasifikasi::class, 'klasifikasi_id');
    }

    // Relasi: Klasifikasi punya banyak SBU
    public function sbus()
    {
        return $this->hasMany(Sbu::class, 'klasifikasi_id');
    }
}
