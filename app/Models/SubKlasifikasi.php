<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubKlasifikasi extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'sbu_code', 'klasifikasi_id'];

    // Relasi ke Klasifikasi
    public function klasifikasi()
    {
        return $this->belongsTo(Klasifikasi::class);
    }
}
