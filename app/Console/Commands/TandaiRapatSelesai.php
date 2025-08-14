<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rapat;
use Carbon\Carbon;

class TandaiRapatSelesai extends Command
{
    protected $signature = 'rapat:selesai';
    protected $description = 'Menandai rapat yang sudah lewat tanggal dan waktunya sebagai selesai';

    public function handle()
    {
        $now = Carbon::now();
        $this->info("⏳ Mengecek rapat yang waktunya sudah lewat...");

        $rapats = Rapat::where('status', 'finalisasi')
            ->whereDate('tanggal_terpilih', '<=', $now->toDateString())
            ->get();

        if ($rapats->isEmpty()) {
            $this->info("✅ Tidak ada rapat yang perlu ditandai selesai.");
            return Command::SUCCESS;
        }

        foreach ($rapats as $rapat) {
            // Gabungkan tanggal_terpilih + waktu_mulai menjadi satu datetime
            $rapatDateTime = Carbon::parse("{$rapat->tanggal_terpilih} {$rapat->waktu_mulai}");

            // Jika sudah lewat dari sekarang, tandai sebagai selesai
            if ($rapatDateTime->lessThanOrEqualTo($now)) {
                $rapat->update([
                    'status' => 'selesai'
                ]);
                $this->info("✅ Rapat ID {$rapat->id} telah selesai (jadwal: {$rapatDateTime->format('Y-m-d H:i')}).");
            } else {
                $this->line("⏳ Rapat ID {$rapat->id} belum dimulai (jadwal: {$rapatDateTime->format('Y-m-d H:i')}).");
            }
        }

        $this->info("🎉 Proses selesai.");
        return Command::SUCCESS;
    }
}
