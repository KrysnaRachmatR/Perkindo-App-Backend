<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubKlasifikasi extends Model
{
    use HasFactory;

    protected $table = 'sub_klasifikasi';

    protected $fillable = [
        'klasifikasi_id',
        'kode',
        'nama',
        'deskripsi',
    ];

    // Relasi: SubKlasifikasi milik 1 Klasifikasi
    public function klasifikasi()
    {
        return $this->belongsTo(Klasifikasi::class, 'klasifikasi_id');
    }

    // Relasi: SubKlasifikasi punya banyak SBU
    public function sbus()
    {
        return $this->hasMany(Sbu::class, 'sub_klasifikasi_id');
    }
}
