<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserDetailController extends Controller
{
    public function indexNonKonstruksi()
    {
        // Ambil semua user yang punya sbunRegistrations dengan status "approve" dan aktif
        $users = User::with([
            'sbunRegistrations.nonKonstruksiKlasifikasi',
            'sbunRegistrations.nonKonstruksiSubKlasifikasi'
        ])->whereHas('sbunRegistrations', function ($query) {
            $query->where('status_diterima', 'approve')
                  ->where('status_aktif', 'active');
        })->get();
    
        $data = $users->map(function ($user, $index) {
            $registrations = $user->sbunRegistrations
                ->where('status_diterima', 'approve')
                ->where('status_aktif', 'active')
                ->map(function ($registration) {
                    $tanggalDiterima = $registration->tanggal_diterima;
                    $tanggalExpired = $tanggalDiterima
                        ? \Carbon\Carbon::parse($tanggalDiterima)->addYear()->toDateString()
                        : '-';

                    return [
                        'klasifikasi' => $registration->nonKonstruksiKlasifikasi->nama ?? '-',
                        'sub_klasifikasi' => $registration->nonKonstruksiSubKlasifikasi->nama ?? '-',
                        'kode_sbu' => $registration->nonKonstruksiSubKlasifikasi->sbu_code ?? '-',
                        'tanggal_diterima' => $tanggalDiterima ?? '-',
                        'tanggal_expired' => $tanggalExpired,
                        'status' => $registration->status_aktif ?? 'inactive',
                    ];
                });

            return [
                'no' => $index + 1,
                'nama_perusahaan' => $user->nama_perusahaan,
                'nama_direktur' => $user->nama_direktur,
                'nama_penanggung_jawab' => $user->nama_penanggung_jawab,
                'alamat_perusahaan' => $user->alamat_perusahaan,
                'sbu_non_konstruksi' => $registrations,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function indexKonstruksi()
    {
        // Ambil semua user yang punya sbusRegistrations yang sudah disetujui dan aktif
        $users = User::with([
            'sbusRegistrations.konstruksiKlasifikasi',
            'sbusRegistrations.konstruksiSubKlasifikasi'
        ])->whereHas('sbusRegistrations', function ($query) {
            $query->where('status_diterima', 'approve')
                  ->where('status_aktif', 'active');
        })->get();
    
        $data = $users->map(function ($user, $index) {
            $registrations = $user->sbusRegistrations
                ->where('status_diterima', 'approve')
                ->where('status_aktif', 'active')
                ->map(function ($registration) {
                    $tanggalDiterima = $registration->tanggal_diterima;
                    $tanggalExpired = $tanggalDiterima
                        ? \Carbon\Carbon::parse($tanggalDiterima)->addYears(3)->toDateString()
                        : '-';

                    return [
                        'klasifikasi' => $registration->konstruksiKlasifikasi->nama ?? '-',
                        'sub_klasifikasi' => $registration->konstruksiSubKlasifikasi->nama ?? '-',
                        'kode_sbu' => $registration->konstruksiSubKlasifikasi->sbu_code ?? '-',
                        'tanggal_diterima' => $tanggalDiterima ?? '-',
                        'tanggal_expired' => $tanggalExpired,
                        'status' => $registration->status_aktif ?? 'inactive',
                    ];
                });

            return [
                'no' => $index + 1,
                'nama_perusahaan' => $user->nama_perusahaan,
                'nama_direktur' => $user->nama_direktur,
                'nama_penanggung_jawab' => $user->nama_penanggung_jawab,
                'alamat_perusahaan' => $user->alamat_perusahaan,
                'sbu_konstruksi' => $registrations,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}
