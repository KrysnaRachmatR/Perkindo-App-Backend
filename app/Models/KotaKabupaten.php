<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KotaKabupaten extends Model
{
    use HasFactory;

    // Nama tabel (opsional)
    protected $table = 'kota_kabupatens';

    // Kolom yang bisa diisi
    protected $fillable = [
        'nama',
    ];

    // Relasi One-to-Many: Satu Kota/Kabupaten bisa memiliki banyak KTA
    public function ktas()
    {
        return $this->hasMany(KTA::class, 'kabupaten_id');
    }
}
