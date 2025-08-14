<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PollingTanggal extends Model
{
    use HasFactory;

    protected $table = 'polling_tanggals';

    protected $fillable = [
        'id_rapat',
        'id_user',
        'tanggal_yang_dipilih',
        'status',
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
