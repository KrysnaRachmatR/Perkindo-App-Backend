<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateSBUStatus extends Command
{
    protected $signature = 'sbus:update-status';
    protected $description = 'Update status_aktif menjadi will_expired atau expired berdasarkan tanggal expired_at';

    public function handle()
    {
        $today = Carbon::today(); // Lebih konsisten untuk perbandingan tanggal
        $sevenDaysLater = $today->copy()->addDays(30);

        // 1. Update ke "will_expired" jika expired dalam 30 hari ke depan
        $willExpireCount = DB::table('sbus_registration')
            ->where('status_aktif', 'active')
            ->whereBetween('expired_at', [$today->addDay(), $sevenDaysLater])
            ->update(['status_aktif' => 'will_expired']);

        // 2. Update ke "expired" jika sudah melewati tanggal expired
        $expiredCount = DB::table('sbus_registration')
            ->whereIn('status_aktif', ['active', 'will_expired'])
            ->whereDate('expired_at', '<=', $today)
            ->update(['status_aktif' => 'expired']);

        $this->info("Status diperbarui: {$willExpireCount} will_expired, {$expiredCount} expired.");
    }
}
