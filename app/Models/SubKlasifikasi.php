<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubKlasifikasi extends Model
{
    protected $fillable = ['klasifikasi_id', 'nama_sub_klasifikasi'];

    // Relasi: Sub Klasifikasi memiliki satu Klasifikasi
    public function klasifikasi()
    {
        return $this->belongsTo(Klasifikasi::class);
    }

    // Relasi: Sub Klasifikasi memiliki satu Kode SBU
    public function sbuCode()
    {
        return $this->hasOne(SbuCode::class, 'sub_klasifikasi_id');
    }
}
