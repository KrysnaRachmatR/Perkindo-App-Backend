<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateSBUStatus extends Command
{
    protected $signature = 'sbun:update-status';
    protected $description = 'Update status_aktif menjadi will_expired atau expired berdasarkan tanggal expired_at';

    public function handle()
    {
        $today = Carbon::now();
        $sevenDaysLater = $today->copy()->addDays(7);

        // Update yang akan expired dalam 7 hari ke depan jadi "will_expired"
        DB::table('sbun_registration')
            ->where('status_aktif', 'active')
            ->whereDate('expired_at', '>', $today)
            ->whereDate('expired_at', '<=', $sevenDaysLater)
            ->update(['status_aktif' => 'will_expired']);

        // Update yang sudah lewat tanggal expired jadi "expired"
        DB::table('sbun_registration')
            ->whereIn('status_aktif', ['active', 'will_expired'])
            ->whereDate('expired_at', '<=', $today)
            ->update(['status_aktif' => 'expired']);

        $this->info('Status berhasil diperbarui ke will_expired dan expired.');
    }
}
