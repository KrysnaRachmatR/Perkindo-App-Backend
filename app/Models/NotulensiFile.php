<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotulensiFile extends Model
{
    protected $fillable = [
        'notulensi_id',
        'file_path',
        'original_name'
    ];

    public function notulensi()
    {
        return $this->belongsTo(Notulensi::class);
    }
}