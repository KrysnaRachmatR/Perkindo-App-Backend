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
        'no_hp_direktur',
        'no_hp_perusahaan',
        'alamat_perusahaan',
        'logo_perusahaan',
        'nama_penanggung_jawab',
        'no_hp_penanggung_jawab',
        'ktp_penanggung_jawab',
        'npwp_penanggung_jawab',
        'nama_pemegang_saham',
        'no_hp_pemegang_saham',
        'ktp_pemegang_saham',
        'npwp_pemegang_saham',
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
