<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens; // Pastikan ini ditambahkan

class Member extends Model
{
    use HasFactory, HasApiTokens; // Tambahkan HasApiTokens di sini

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
}
