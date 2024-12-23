<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserDetailController extends Controller
{
  public function index()
  {
    // Mengambil data user beserta relasi SBU Konstruksi, Non-Konstruksi, dan KTA
    $users = User::with([
      'sbusRegistrations.konstruksiKlasifikasi',
      'sbusRegistrations.konstruksiSubKlasifikasi',
      'sbunRegistrations.nonKonstruksiKlasifikasi',
      'sbunRegistrations.nonKonstruksiSubKlasifikasi',
      'KTA'
    ])->get();

    // Mapping data user dan relasi ke dalam array yang terstruktur
    $data = $users->map(function ($user) {
      $sbuKonstruksi = $user->sbusRegistrations->first();  // Ambil SBU Konstruksi pertama
      $sbunNonKonstruksi = $user->sbunRegistrations->first(); // Ambil SBU Non Konstruksi pertama
      $ktaStatus = $user->KTA->status_diterima ?? 'N/A'; // Status KTA (jika ada)
      $ktaDiterima = $user->KTA->tanggal_diterima ?? 'N/A'; // Tanggal diterima KTA (jika ada)
      $sbusDiterima = $sbuKonstruksi->status_aktif ?? 'N/A'; // Status aktif SBU Konstruksi (jika ada)
      $sbunDiterima = $sbunNonKonstruksi->status_aktif ?? 'N/A'; // Status aktif SBU Non Konstruksi (jika ada)

      // Tentukan status KTA
      $status = ($ktaStatus == 'pending') ? 'rejected' : 'accepted';

      // Ambil semua klasifikasi dan sub-klasifikasi berdasarkan tipe SBU
      $klasifikasi_sbus = $user->sbusRegistrations->map(function ($sbu) {
        return $sbu->konstruksiKlasifikasi->nama ?? 'N/A';
      })->toArray();

      $sub_klasifikasi_sbus = $user->sbusRegistrations->map(function ($sbu) {
        return $sbu->konstruksiSubKlasifikasi->nama ?? 'N/A';
      })->toArray();

      $klasifikasi_sbun = $user->sbunRegistrations->map(function ($sbun) {
        return $sbun->nonKonstruksiKlasifikasi->nama ?? 'N/A';
      })->toArray();

      $sub_klasifikasi_sbun = $user->sbunRegistrations->map(function ($sbun) {
        return $sbun->nonKonstruksiSubKlasifikasi->nama ?? 'N/A';
      })->toArray();

      return [
        'nama_perusahaan' => $user->nama_perusahaan,
        'nama_direktur' => $user->nama_direktur,
        'nama_penanggung_jawab' => $user->nama_penanggung_jawab,
        'alamat_perusahaan' => $user->alamat_perusahaan,
        'status_KTA' => $status,
        'tanggal_diterima' => $ktaDiterima,
        'status_SBU_Konstruksi' => $sbusDiterima,
        'status_SBU_Non_Konstruksi' => $sbunDiterima,
        'klasifikasi_sbus' => $klasifikasi_sbus,
        'sub_klasifikasi_sbus' => $sub_klasifikasi_sbus,
        'klasifikasi_sbun' => $klasifikasi_sbun,
        'sub_klasifikasi_sbun' => $sub_klasifikasi_sbun,
      ];
    });

    // Return response JSON
    return response()->json([
      'status' => 'success',
      'data' => $data
    ]);
  }
}
