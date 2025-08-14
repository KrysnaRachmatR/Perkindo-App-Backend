<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PesertaRapat extends Model
{
    use HasFactory;

   protected $table = 'peserta_rapats';

   protected $fillable = [
        'rapat_id', 
        'id_user', 
        'is_pengurus',
        'jabatan',
        'hadir',
        'catatan_pribadi',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function rapat()
    {
        return $this->belongsTo(Rapat::class, 'rapat_id');
    }

}