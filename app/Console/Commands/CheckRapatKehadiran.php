<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rapat;
use App\Models\PollingTanggal;
use App\Models\KesediaanHadir;
use App\Mail\UndanganRapatMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class CheckRapatKehadiran extends Command
{
    protected $signature = 'rapat:check-kehadiran';
    protected $description = 'Cek status kehadiran dan kirim undangan jika kuorum (50%) peserta hadir';

    public function handle()
{
    $this->info("🚀 Memulai pengecekan status rapat...");

    $rapats = Rapat::with('pesertaRapats.user')
        ->whereIn('status', ['finalisasi','menunggu_polling_tanggal'])
        ->get();

    if ($rapats->isEmpty()) {
        $this->warn("⚠️ Tidak ada rapat dengan status yang valid untuk dicek.");
        return Command::SUCCESS;
    }

    foreach ($rapats as $rapat) {
        $this->line("🔎 Mengecek Rapat ID: {$rapat->id}");

        $totalPeserta = $rapat->pesertaRapats->count();
        if ($totalPeserta === 0) {
            $this->warn("⚠️ Rapat ID {$rapat->id} tidak memiliki peserta.");
            continue;
        }

        if ($rapat->status === 'finalisasi') {
            // Cek apakah sudah terkirim (PDF tersedia di storage)
            if ($rapat->file_undangan_pdf && Storage::disk('public')->exists($rapat->file_undangan_pdf)) {
                $this->info("📂 Undangan Rapat ID {$rapat->id} sudah dikirim sebelumnya. Melewati...");
                continue;
            }

            $jumlahHadir = KesediaanHadir::where('id_rapat', $rapat->id)
                ->where('status', 'hadir')
                ->count();

            $persentaseHadir = ($jumlahHadir / $totalPeserta) * 100;

            if ($persentaseHadir >= 50) {
                if (!$rapat->tanggal_terpilih) {
                    $this->warn("📛 Rapat ID {$rapat->id} belum bisa difinalisasi karena tanggal_terpilih belum ada.");
                    continue;
                }

                $this->info("✅ Rapat ID {$rapat->id} mencapai kuorum ({$persentaseHadir}%). Mengirim undangan...");
                $this->kirimUndangan($rapat);
            } else {
                $rapat->update(['status' => 'menunggu_polling_tanggal']);
                $this->warn("❌ Rapat ID {$rapat->id} belum kuorum ({$persentaseHadir}%). Diubah ke menunggu_polling_tanggal.");
            }
        }

        if ($rapat->status === 'menunggu_polling_tanggal') {
            $totalPolling = PollingTanggal::where('id_rapat', $rapat->id)->count();
            $uniqueUserPolling = PollingTanggal::where('id_rapat', $rapat->id)
                ->distinct('id_user')
                ->count('id_user');

            if ($uniqueUserPolling >= $totalPeserta) {
                $this->info("📊 Semua peserta sudah memilih tanggal. Memilih tanggal terbanyak...");

                $tanggalMenang = PollingTanggal::where('id_rapat', $rapat->id)
                    ->select('tanggal_yang_dipilih', \DB::raw('count(*) as jumlah'))
                    ->groupBy('tanggal_yang_dipilih')
                    ->orderByDesc('jumlah')
                    ->first();

                if ($tanggalMenang) {
                    $rapat->update([
                        'tanggal_terpilih' => $tanggalMenang->tanggal_yang_dipilih,
                        'status' => 'finalisasi',
                        'pengiriman_dijadwalkan_pada' => $rapat->pengiriman_dijadwalkan_pada ?? now(),
                    ]);

                    $this->info("📅 Tanggal terpilih untuk Rapat ID {$rapat->id}: {$tanggalMenang->tanggal_yang_dipilih}");
                    $this->kirimUndangan($rapat);
                } else {
                    $this->warn("❌ Tidak ada tanggal voting ditemukan untuk Rapat ID {$rapat->id}.");
                }
            } elseif ($rapat->tanggal_polling_berakhir && Carbon::now()->gt($rapat->tanggal_polling_berakhir)) {
                $this->warn("⏱️ Polling untuk Rapat ID {$rapat->id} sudah berakhir, tapi belum semua memilih.");
                $rapat->update(['status' => 'dibatalkan']);
                $this->warn("❌ Rapat ID {$rapat->id} dibatalkan karena polling kedaluwarsa.");
            } else {
                $this->info("⏳ Rapat ID {$rapat->id}: Belum semua peserta polling ({$uniqueUserPolling}/{$totalPeserta}).");
            }
        }
    }

    $this->info("✅ Proses pengecekan selesai.");
    return Command::SUCCESS;
}

protected function kirimUndangan($rapat)
{
    try {
        // Cek apakah sudah ada file undangan
        if ($rapat->file_undangan_pdf && Storage::disk('public')->exists($rapat->file_undangan_pdf)) {
            $this->info("📂 Undangan Rapat ID {$rapat->id} sudah ada. Tidak mengirim ulang.");
            return;
        }

        // Buat dan simpan file PDF
        $pdf = PDF::loadView('pdf.undangan', ['rapat' => $rapat]);
        $filename = "undangan/rapat-{$rapat->id}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        // Update hanya jika belum ada pengiriman sebelumnya
        $rapat->update([
            'file_undangan_pdf' => $filename,
            'pengiriman_dijadwalkan_pada' => $rapat->pengiriman_dijadwalkan_pada ?? now(),
        ]);

        // Kirim email ke peserta
        foreach ($rapat->pesertaRapats as $peserta) {
            $user = $peserta->user;
            if ($user && $user->email) {
                Mail::to($user->email)->send(new UndanganRapatMail($rapat));
            }
        }

        $this->info("📩 Undangan rapat ID {$rapat->id} berhasil dikirim.");
    } catch (\Exception $e) {
        $this->error("💥 Gagal mengirim undangan untuk rapat ID {$rapat->id}: " . $e->getMessage());
    }
}

}
