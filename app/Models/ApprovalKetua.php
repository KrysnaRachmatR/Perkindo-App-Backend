<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApprovalKetua extends Model
{
    use HasFactory;

    protected $table = 'approval_ketuas';

    protected $fillable = [
        'id_rapat',
        'id_ketua',
        'status',
        'catatan',
        'approved_at',
    ];

    public function rapat()
    {
        return $this->belongsTo(Rapat::class, 'id_rapat');
    }

    public function ketua()
    {
        return $this->belongsTo(User::class, 'id_ketua');
    }
}
