<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anggota extends Model
{
    use HasFactory;

    protected $table = 'anggota';

    protected $fillable = [
        'nama_badan_usaha',
        'alamat',
        'direktur',
        'kode_sbu',
        'tanggal_masa_berlaku',
        'sampai_dengan',
        'jenis_sbu',
        'status_aktif',
    ];

    public $timestamps = true;

    public function checkStatusAktif()
    {
        $currentDate = Carbon::now();
        $masaBerlaku = Carbon::createFromFormat('Y-m-d', $this->sampai_dengan);

        // Mengatur status aktif berdasarkan masa berlaku
        $this->status_aktif = $masaBerlaku->greaterThanOrEqualTo($currentDate);
        $this->save();
    }
}
