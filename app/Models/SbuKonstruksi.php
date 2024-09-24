<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SbuKonstruksi extends Model
{
    use HasFactory;

    protected $table = 'sbu_konstruksi';
    protected $fillable = [
        'no',
        'nama_badan_usaha',
        'alamat',
        'direktur',
        'kode_sbu',
        'tanggal_masa_berlaku',
        'sampai_dengan'
    ];
}
