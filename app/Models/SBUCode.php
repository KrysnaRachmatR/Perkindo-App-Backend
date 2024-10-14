<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SbuCode extends Model
{
    protected $fillable = ['sub_klasifikasi_id', 'kode_sbu', 'kbli'];

    // Relasi: Kode SBU dimiliki oleh satu Sub Klasifikasi
    public function subKlasifikasi()
    {
        return $this->belongsTo(SubKlasifikasi::class);
    }
}
