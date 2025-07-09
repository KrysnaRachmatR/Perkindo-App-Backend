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
        $now = Carbon::now();

        $rapats = Rapat::where('status', 'finalisasi')
            ->where(function ($query) use ($now) {
                $query->where(function ($q) use ($now) {
                    $q->whereNotNull('jam')
                        ->whereRaw("STR_TO_DATE(CONCAT(tanggal_terpilih, ' ', jam), '%Y-%m-%d %H:%i') < ?", [$now]);
                })->orWhere(function ($q) use ($now) {
                    $q->whereNull('jam')
                        ->where('tanggal_terpilih', '<', $now->toDateString());
                });
            })->get();

        foreach ($rapats as $rapat) {
            $rapat->status = 'selesai';
            $rapat->save();
            $this->info("Rapat #{$rapat->id} diubah ke selesai.");
        }

        $this->info("Total: {$rapats->count()} rapat diperbarui.");
    }

}