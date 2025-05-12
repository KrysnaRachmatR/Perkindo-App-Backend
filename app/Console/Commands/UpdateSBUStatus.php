<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateSBUStatus extends Command
{
    protected $signature = 'sbun:update-status';
    protected $description = 'Update status_aktif menjadi will_expired atau expired berdasarkan tanggal expired_at pada SBU Non Konstruksi';

    public function handle()
    {
        $today = Carbon::today(); // Gunakan 'today()' agar perbandingan hanya tanggal
        $sevenDaysLater = $today->copy()->addDays(30);

        // 1. Update yang akan expired dalam 7 hari ke depan jadi "will_expired"
        $willExpireCount = DB::table('sbun_registration')
            ->where('status_aktif', 'active')
            ->whereBetween('expired_at', [$today->addDay(), $sevenDaysLater])
            ->update(['status_aktif' => 'will_expired']);

        // 2. Update yang sudah lewat tanggal expired jadi "expired"
        $expiredCount = DB::table('sbun_registration')
            ->whereIn('status_aktif', ['active', 'will_expired'])
            ->whereDate('expired_at', '<=', $today)
            ->update(['status_aktif' => 'expired']);

        $this->info("SBU Non-Konstruksi status diperbarui: {$willExpireCount} will_expired, {$expiredCount} expired.");
    }
}
