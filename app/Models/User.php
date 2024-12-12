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
    public function KTA()
    {
        return $this->hasOne(KTA::class, 'user_id');
    }

    public function SBUSRegistrations()
    {
        return $this->hasMany(SBURegistrations::class, 'user_id');
    }
    public function SBUNRegistrations()
    {
        return $this->hasMany(SBUNRegistrations::class, 'user_id');
    }
}
