<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserDetailController extends Controller
{
    public function indexNonKonstruksi()
    {
        // Ambil semua user yang punya sbunRegistrations dengan status "approve" dan status aktif "active"
        $users = User::with([
            'sbunRegistrations.nonKonstruksiKlasifikasi',
            'sbunRegistrations.nonKonstruksiSubKlasifikasi'
        ])->whereHas('sbunRegistrations', function ($query) {
            $query->where('status_diterima', 'approve')
                  ->where('status_aktif', 'active');
        })->get();
    
        // Map user dan semua SBU Non Konstruksinya
        $data = $users->map(function ($user, $index) {
            $validRegistrations = $user->sbunRegistrations
                ->where('status_diterima', 'approve')
                ->where('status_aktif', 'active');
    
            $registrasiDetails = $validRegistrations->map(function ($registration) {
                $tanggalDiterima = $registration->tanggal_diterima ?? null;
                $tanggalExpired = $tanggalDiterima
                    ? date('Y-m-d', strtotime("+1 years", strtotime($tanggalDiterima)))
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
                'sbu_non_konstruksi' => $registrasiDetails,
            ];
        });
    
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function indexKonstruksi()
{
    // Ambil semua user yang punya sbukRegistrations yang sudah disetujui dan aktif
    $users = User::with([
        'sbusRegistrations.konstruksiKlasifikasi',
        'sbusRegistrations.konstruksiSubKlasifikasi'
    ])->whereHas('sbusRegistrations', function ($query) {
        $query->where('status_diterima', 'approve')
              ->where('status_aktif', 'active');
    })->get();

    // Mapping data untuk setiap user
    $data = $users->map(function ($user, $index) {
        $validRegistrations = $user->sbukRegistrations
            ->where('status_diterima', 'approve')
            ->where('status_aktif', 'active');

        $registrasiDetails = $validRegistrations->map(function ($registration) {
            $tanggalDiterima = $registration->tanggal_diterima ?? null;
            $tanggalExpired = $tanggalDiterima
                ? date('Y-m-d', strtotime("+3 years", strtotime($tanggalDiterima)))
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
            'sbu_konstruksi' => $registrasiDetails,
        ];
    });

    return response()->json([
        'status' => 'success',
        'data' => $data
    ]);
}

    
}
