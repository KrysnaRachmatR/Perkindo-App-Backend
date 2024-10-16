<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SbuCode extends Model
{
    protected $fillable = ['kode', 'sub_klasifikasi_id'];

    public function subKlasifikasi()
    {
        return $this->belongsTo(SubKlasifikasi::class);
    }
}
