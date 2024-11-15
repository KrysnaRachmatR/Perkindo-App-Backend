<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekeningTujuan extends Model
{
    use HasFactory;

    protected $table = 'rekening_tujuan';
    protected $fillable = [
        'nama_bank',
        'nomor_rekening',
        'atas_nama',
    ];

    public function ktas()
    {
        return $this->hasMany(KTA::class, 'rekening_id');
    }

    public function sbusRegistrations()
    {
        return $this->hasMany(SbuRegistrations::class);
    }

    public function sbunRegistrations()
    {
        return $this->hasMany(SbunRegistrations::class);
    }
}
