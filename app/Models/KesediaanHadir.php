<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KesediaanHadir extends Model
{
    use HasFactory;

    protected $table = 'kesediaan_hadir';

    protected $fillable = [
        'id_rapat',
        'id_user',
        'alasan',
        'status', // ya / tidak
    ];

    public function rapat()
    {
        return $this->belongsTo(Rapat::class, 'id_rapat');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
