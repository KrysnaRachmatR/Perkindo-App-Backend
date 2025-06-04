<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rapat;
use Carbon\Carbon;

class UpdateRapatSelesai extends Command
{
    protected $signature = 'rapat:update-selesai';
    protected $description = 'Update status rapat menjadi selesai jika tanggal_final sudah lewat';

    public function handle()
    {
        $today = Carbon::today();

        $rapats = Rapat::where('status', 'finalisasi')
            ->whereDate('tanggal_final', '<', $today)
            ->get();

        foreach ($rapats as $rapat) {
            $rapat->update(['status' => 'selesai']);
            $this->info("Rapat #{$rapat->id} diubah ke status selesai.");
        }

        return 0;
    }
}
