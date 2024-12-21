<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonKonstruksiSubKlasifikasi extends Model
{
    use HasFactory;

    protected $table = 'non_konstruksi_sub_klasifikasis'; // Nama tabel

    protected $fillable = [
        'klasifikasi_id',
        'nama',
        'sbu_code',
    ];

    /**
     * Relasi ke klasifikasi
     *
     * @return BelongsTo
     */
    public function klasifikasi(): BelongsTo
    {
        return $this->belongsTo(NonKonstruksiKlasifikasi::class, 'klasifikasi_id');
    }

    public function sbusRegistrations()
    {
        return $this->hasMany(SBUNRegistration::class);
    }
}
