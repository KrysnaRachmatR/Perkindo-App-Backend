<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollingTanggalOption extends Model
{
    protected $fillable = ['id_rapat', 'tanggal'];

    public function rapat()
    {
        return $this->belongsTo(Rapat::class, 'id_rapat');
    }
}
