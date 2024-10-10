<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Member extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nama_perusahaan',
        'nama_direktur',
        'nama_penanggung_jawab',
        'alamat_perusahaan',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    // Optional: jika menggunakan timestamps
    public $timestamps = true;
}
