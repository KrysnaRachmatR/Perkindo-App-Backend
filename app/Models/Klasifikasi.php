<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Klasifikasi extends Model
{
    protected $fillable = ['nama_klasifikasi'];

    // Relasi: Klasifikasi memiliki banyak Sub Klasifikasi
    public function subKlasifikasis()
    {
        return $this->hasMany(SubKlasifikasi::class);
    }
}
