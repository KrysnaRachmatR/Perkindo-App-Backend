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
        'user_id', 
        'is_pengurus',
        'jabatan',
        'hadir',
        'catatan_pribadi',
    ];

    public function user()
{
    return $this->belongsTo(User::class);
}

public function rapat()
{
    return $this->belongsTo(Rapat::class);
}

}