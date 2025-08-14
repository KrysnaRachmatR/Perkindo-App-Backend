<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notulensi extends Model
{
    use HasFactory;

    protected $table = 'notulensis';

    protected $fillable = [
        'id_rapat',
        'dibuat_oleh',
        'dibuat_oleh_admin',
        'isi',
        'status',
        'file_pdf_path',
        'approved_at',
        'catatan_penolakan',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // Relasi ke rapat
    public function rapat()
    {
        return $this->belongsTo(Rapat::class, 'id_rapat');
    }

    // Relasi ke user (pembuat)
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function pembuatAdmin()
    {
        return $this->belongsTo(Admin::class, 'dibuat_oleh_admin');
    }

    public function files()
    {
        return $this->hasMany(NotulensiFile::class);
    }
}
