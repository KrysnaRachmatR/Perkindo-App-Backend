<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateKTAStatus extends Command
{
    protected $signature = 'kta:update-status';
    protected $description = 'Update status_aktif KTA menjadi will_expired atau expired berdasarkan tanggal expired_at';

    public function handle()
    {
        $today = Carbon::today();
        $sevenDaysLater = $today->copy()->addDays(30);

        // 1. Update ke "will_expired"
        $willExpireCount = DB::table('ktas')
            ->where('status_aktif', 'active')
            ->whereBetween('expired_at', [$today->addDay(), $sevenDaysLater])
            ->update(['status_aktif' => 'will_expired']);

        // 2. Update ke "expired"
        $expiredCount = DB::table('ktas')
            ->whereIn('status_aktif', ['active', 'will_expired'])
            ->whereDate('expired_at', '<=', $today)
            ->update(['status_aktif' => 'expired']);

        $this->info("Status KTA diperbarui: {$willExpireCount} will_expired, {$expiredCount} expired.");
    }
}
