<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // WAJIB untuk Sanctum
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // WAJIB untuk Sanctum
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notulen extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'notulens';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Relasi ke tabel meeting_notes
     */
    public function meetingNotes()
    {
        return $this->hasMany(MeetingNote::class);
    }
}
