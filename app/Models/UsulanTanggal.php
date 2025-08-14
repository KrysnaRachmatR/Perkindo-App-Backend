<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsulanTanggal extends Model
{
    use HasFactory;

    protected $table = 'usulan_tanggals';

    protected $fillable = [
        'id_rapat',
        'tanggal_usulan',
        'diusulkan_oleh',
    ];

    public function rapat()
    {
        return $this->belongsTo(Rapat::class, 'id_rapat');
    }

    public function pengusul()
    {
        return $this->belongsTo(User::class, 'diusulkan_oleh');
    }
}
