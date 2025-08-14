<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\PesertaRapat;


class Rapat extends Model
{
    use HasFactory;

    protected $table = 'rapats';

    protected $fillable = [
        'judul',
        'agenda',
        'lokasi',
        'urgensi',
        'tanggal_polling_berakhir',
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

    protected $casts = [
        'topik' => 'array',
        'tanggal_polling_berakhir' => 'date',
        'tanggal_terpilih' => 'date',
        // 'jam' => 'datetime:H:i',
        'pengiriman_dijadwalkan_pada' => 'datetime',
    ];

    // Relasi ke peserta rapat
    public function pesertaRapats(): HasMany
    {
        return $this->hasMany(PesertaRapat::class, 'rapat_id');
    }

    // Relasi ke admin pembuat rapat
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    // Relasi ke notulensi
    public function notulensi(): HasOne
    {
        return $this->hasOne(Notulensi::class, 'id_rapat');
    }

    // Relasi ke approval ketua
    public function approvalKetua()
    {
        return $this->hasOne(ApprovalKetua::class, 'id_rapat');
    }

    // Relasi ke polling tanggal
    public function pollingTanggal(): HasMany
    {
        return $this->hasMany(PollingTanggal::class, 'id_rapat');
    }

    // Relasi ke kesediaan hadir
    public function kesediaanHadir(): HasMany
    {
        return $this->hasMany(KesediaanHadir::class, 'id_rapat');
    }

    // Relasi ke absensi
    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class, 'id_rapat');
    }

    public function pollingTanggalOptions()
    {
        return $this->hasMany(PollingTanggalOption::class, 'id_rapat');
    }


    // Auto delete peserta saat rapat dihapus
    protected static function booted(): void
    {
        static::deleting(function (Rapat $rapat) {
            $rapat->pesertaRapats()->delete();
        });
    }
}
