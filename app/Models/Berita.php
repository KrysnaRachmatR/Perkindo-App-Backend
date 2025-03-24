<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Berita extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'caption', 'image'];

    protected $casts = [
        'image' => 'string',
    ];

    public function komentars()
    {
        return $this->hasMany(Komentar::class);
    }
}
