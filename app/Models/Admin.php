<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class Admin extends Model implements AuthenticatableContract
{
    use HasFactory, Notifiable, Authenticatable, HasApiTokens;

    protected $table = 'admins';
    protected $fillable = ['name', 'username', 'email', 'password'];
    public $timestamps = true;

    protected $hidden = ['password'];

   public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

     public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }
}
