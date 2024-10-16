<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubKlasifikasiNonKonstruksi extends Model
{
    use HasFactory;

    protected $table = 'sub_klasifikasi_non_konstruksi'; // Nama tabel

    protected $fillable = [
        'nama',
        'sbu_code',
        'klasifikasi_id',
    ];

    /**
     * Relasi ke klasifikasi
     *
     * @return BelongsTo
     */
    public function klasifikasi(): BelongsTo
    {
        return $this->belongsTo(KlasifikasiNonKonstruksi::class, 'klasifikasi_id');
    }
}
