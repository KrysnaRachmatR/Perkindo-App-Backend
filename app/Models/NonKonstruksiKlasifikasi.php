<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KlasifikasiNonKonstruksi extends Model
{
    use HasFactory;

    protected $table = 'klasifikasi_non_konstruksi'; // Nama tabel

    protected $fillable = [
        'nama',
    ];

    /**
     * Relasi ke sub klasifikasi
     *
     * @return HasMany
     */
    public function subKlasifikasis(): HasMany
    {
        return $this->hasMany(SubKlasifikasiNonKonstruksi::class, 'klasifikasi_id');
    }
}
