<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Klasifikasi extends Model
{
    use HasFactory;

    protected $fillable = ['nama'];

    public function subKlasifikasis()
    {
        return $this->hasMany(SubKlasifikasi::class);
    }

    public function sbusRegistrations()
    {
        return $this->hasMany(SbusRegistration::class);
    }
}
