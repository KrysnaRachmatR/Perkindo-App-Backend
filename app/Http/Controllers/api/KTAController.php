<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KTA;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KtaController extends Controller
{
  // Metode untuk menyimpan KTA baru
  public function store(Request $request)
  {
    // Validasi input
    $request->validate([
      'formulir_permohonan' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'pernyataan_kebenaran' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'pengesahan_menkumham' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'akta_pendirian' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
      'akta_perubahan' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
      'npwp_perusahaan' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'surat_domisili' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'ktp_pengurus' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'logo' => 'nullable|file|mimes:jpg,jpeg,png',
      'foto_direktur' => 'required|file|mimes:jpg,jpeg,png',
      'npwp_pengurus_akta' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'bukti_transfer' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'kabupaten_id' => 'required|exists:kota_kabupaten,id',
    ]);

    // Simpan dokumen dan dapatkan pathnya
    $paths = [];
    $files = [
      'formulir_permohonan',
      'pernyataan_kebenaran',
      'pengesahan_menkumham',
      'akta_pendirian',
      'akta_perubahan',
      'npwp_perusahaan',
      'surat_domisili',
      'ktp_pengurus',
      'logo',
      'foto_direktur',
      'npwp_pengurus_akta',
      'bukti_transfer'
    ];

    foreach ($files as $file) {
      if ($request->hasFile($file)) {
        $paths[$file] = $request->file($file)->store('uploads/kta', 'public');
      }
    }

    // Buat KTA baru
    try {
      $kta = KTA::create(array_merge($paths, [
        'kabupaten_id' => $request->kabupaten_id,
        'user_id' => Auth::id(),
        'status_perpanjangan_kta' => 'pending', // Status awal
        'tanggal_diterima' => null, // Belum ada tanggal diterima saat dibuat
      ]));

      return response()->json([
        'message' => 'KTA berhasil diajukan.',
        'kta' => $kta,
      ], 201);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Gagal menyimpan KTA: ' . $e->getMessage(),
      ], 500);
    }
  }

  // Metode untuk memperbarui KTA
  public function update(Request $request, KTA $kta)
  {
    // Validasi input untuk update
    $request->validate([
      'status_perpanjangan_kta' => 'required|in:active,inactive,pending,rejected',
      'komentar' => 'nullable|string',
    ]);

    // Update status dan tanggal diterima jika diterima
    if ($request->status_perpanjangan_kta == 'active') {
      $kta->tanggal_diterima = now(); // Set tanggal diterima saat status menjadi aktif
    } elseif ($request->status_perpanjangan_kta == 'rejected') {
      $kta->komentar = $request->komentar; // Simpan komentar jika ditolak
    }

    $kta->status_perpanjangan_kta = $request->status_perpanjangan_kta;
    $kta->save();

    return response()->json([
      'message' => 'Status KTA berhasil diperbarui.',
      'kta' => $kta,
    ]);
  }

  // Metode untuk mengajukan perpanjangan KTA
  public function extend(Request $request, $id)
  {
    $request->validate([
      'bukti_transfer' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $kta = KTA::findOrFail($id);

    // Proses upload bukti transfer
    if ($request->hasFile('bukti_transfer')) {
      $file = $request->file('bukti_transfer');
      $filename = time() . '.' . $file->getClientOriginalExtension();
      $file->storeAs('uploads/bukti_transfer', $filename, 'public');
      $kta->bukti_transfer = $filename; // Simpan nama file
    }

    // Ubah status perpanjangan KTA menjadi pending
    $kta->status_perpanjangan_kta = 'pending';
    $kta->save();

    return response()->json(['message' => 'Perpanjangan KTA diajukan.']);
  }
}
