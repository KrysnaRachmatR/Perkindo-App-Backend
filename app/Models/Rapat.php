<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rapat extends Model
{
    use HasFactory;

    protected $table = 'rapats';

    protected $fillable = [
        'judul',
        'agenda',
        'lokasi',
        'urgensi',
        'tanggal_terpilih',
        'jam',
        'file_undangan_pdf',
        'status',
        'created_by',
        'nomor',
        'lampiran',
        'hal',
        'topik',
        'header_image',
        'tanda_tangan_image',
        'pengiriman_dijadwalkan_pada',
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'topik' => 'array',
        'tanggal_terpilih' => 'date',
    ];

    /**
     * Peserta yang mengikuti rapat.
     */
    public function pesertaRapats(): HasMany
    {
        return $this->hasMany(PesertaRapat::class);
    }

    /**
     * Admin yang membuat rapat.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function notulensi()
    {
        return $this->hasOne(Notulensi::class);
    }

    /**
     * Auto delete relasi peserta saat rapat dihapus.
     */
    protected static function booted(): void
    {
        static::deleting(function (Rapat $rapat) {
            $rapat->pesertaRapats()->delete();
        });
    }
}
