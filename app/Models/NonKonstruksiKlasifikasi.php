<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NonKonstruksiKlasifikasi extends Model
{
    use HasFactory;

    protected $table = 'non_konstruksi_klasifikasis'; // Nama tabel

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
        return $this->hasMany(NonKonstruksiKlasifikasi::class, 'klasifikasi_id');
    }

    public function sbunRegistrations()
    {
        return $this->hasMany(SBUNRegistration::class);
    }
}
