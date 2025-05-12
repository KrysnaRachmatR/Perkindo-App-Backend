<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\KTA;
use App\Models\SbunRegistration;
use App\Models\SbusRegistrations;
use Illuminate\Http\Request;

class UserDetailController extends Controller
{
    public function getDashboardSummary()
    {
        // === Total Aktif ===
        $totalKtaAktif   = KTA::where('status_aktif', 'active')->count();
        $totalSbusAktif  = SbusRegistrations::where('status_aktif', 'active')->count();
        $totalSbunAktif  = SbunRegistration::where('status_aktif', 'active')->count();

        // === Data Harian (per jam) ===
        $today = Carbon::today();
        $hours = collect(range(0, 23));
        $ktaDaily = $this->getCountByHour(KTA::class, $today);
        $sbusDaily = $this->getCountByHour(SbusRegistrations::class, $today);
        $sbunDaily = $this->getCountByHour(SbunRegistration::class, $today);

        // === Data Bulanan (per hari) ===
        $thisMonth = Carbon::now();
        $daysInMonth = $thisMonth->daysInMonth;
        $days = collect(range(1, $daysInMonth));
        $ktaMonthly = $this->getCountByDay(KTA::class, $thisMonth);
        $sbusMonthly = $this->getCountByDay(SbusRegistrations::class, $thisMonth);
        $sbunMonthly = $this->getCountByDay(SbunRegistration::class, $thisMonth);

        // === Data Tahunan (per bulan) ===
        $months = collect(range(1, 12));
        $ktaYearly = $this->getCountByMonth(KTA::class);
        $sbusYearly = $this->getCountByMonth(SbusRegistrations::class);
        $sbunYearly = $this->getCountByMonth(SbunRegistration::class);

        return response()->json([
            'success' => true,
            'total_aktif' => [
                'kta'  => $totalKtaAktif,
                'sbus' => $totalSbusAktif,
                'sbun' => $totalSbunAktif,
            ],
            'chart' => [
                'daily' => $hours->map(fn($hour) => [
                    'hour' => $hour,
                    'kta'  => $ktaDaily[$hour] ?? 0,
                    'sbus' => $sbusDaily[$hour] ?? 0,
                    'sbun' => $sbunDaily[$hour] ?? 0,
                ]),
                'monthly' => $days->map(fn($day) => [
                    'day' => $day,
                    'kta' => $ktaMonthly[$day] ?? 0,
                    'sbus' => $sbusMonthly[$day] ?? 0,
                    'sbun' => $sbunMonthly[$day] ?? 0,
                ]),
                'yearly' => $months->map(fn($month) => [
                    'month' => Carbon::create()->month($month)->format('F'),
                    'kta'   => $ktaYearly[$month] ?? 0,
                    'sbus'  => $sbusYearly[$month] ?? 0,
                    'sbun'  => $sbunYearly[$month] ?? 0,
                ]),
            ]
        ]);
    }

    private function getCountByHour($model, $date)
    {
        return $model::whereDate('created_at', $date)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->groupBy('hour')
            ->pluck('total', 'hour');
    }

    private function getCountByDay($model, $month)
    {
        return $model::whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->selectRaw('DAY(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');
    }

    private function getCountByMonth($model)
    {
        return $model::whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');
    }
}
