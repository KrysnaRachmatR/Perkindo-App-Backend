<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';
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
        'remember_token',
    ];

    // Relasi ke KTA
    public function kta()
    {
        return $this->hasOne(KTA::class, 'user_id');
    }

    // Relasi ke SBUS (SBU Konstruksi)
    public function sbusRegistrations()
    {
        return $this->hasMany(SbusRegistrations::class, 'user_id');
    }

    // Relasi ke SBUN (SBU Non Konstruksi)
    public function sbunRegistrations()
    {
        return $this->hasMany(SbunRegistration::class, 'user_id');
    }
}
