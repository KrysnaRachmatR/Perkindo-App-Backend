<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserDetailController extends Controller
{
  public function index()
  {
    // Mengambil data user beserta data pendaftaran SBU Konstruksi dan Non-Konstruksi
    $users = User::with([
      'SBUSRegistrations.klasifikasi',
      'SBUSRegistrations.subKlasifikasi',
      'SBUNRegistrations.non_konstruksi_klasifikasi',
      'SBUNRegistrations.non_konstruksi_sub_klasifikasi',
      'KTA'
    ])->get();

    // Menyiapkan array untuk menampung data user beserta informasi terkait
    $data = $users->map(function ($user) {
      $sbuKonstruksi = $user->SBUSRegistrations->first(); // Ambil SBU Konstruksi pertama
      $sbunNonKonstruksi = $user->SBUNRegistrations->first(); // Ambil SBU Non Konstruksi pertama
      $ktaStatus = $user->KTA->status ?? 'N/A'; // Cek status KTA (misalnya pending atau approved)
      $ktaDiterima = $user->KTA->tanggal_diterima ?? 'N/A'; // Cek tanggal diterima KTA

      // Tentukan status berdasarkan status KTA
      $status = ($ktaStatus == 'pending') ? 'rejected' : 'accepted';

      return [
        'nama_perusahaan' => $user->nama_perusahaan,
        'nama_direktur' => $user->nama_direktur,
        'nama_penanggung_jawab' => $user->nama_penanggung_jawab,
        'alamat_perusahaan' => $user->alamat_perusahaan,
        'status KTA' => $status, // Status berdasarkan KTA
        'tanggal_diterima' => $ktaDiterima, // Tanggal diterima KTA
        'klasifikasi' => $sbuKonstruksi
          ? $sbuKonstruksi->klasifikasi->nama ?? 'N/A'
          : ($sbunNonKonstruksi
            ? $sbunNonKonstruksi->non_konstruksi_klasifikasi->nama ?? 'N/A'
            : 'N/A'),
        'sub_klasifikasi' => $sbuKonstruksi
          ? $sbuKonstruksi->subKlasifikasi->nama ?? 'N/A'
          : ($sbunNonKonstruksi
            ? $sbunNonKonstruksi->non_konstruksi_sub_klasifikasi->nama ?? 'N/A'
            : 'N/A'),
        'expired_at' => $sbunNonKonstruksi
          ? $sbunNonKonstruksi->expired_at ?? 'N/A'
          : 'N/A',
      ];
    });

    // Mengembalikan data dalam format JSON
    return response()->json([
      'status' => 'success',
      'data' => $data
    ]);
  }
}
