<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KTA;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class KtaController extends Controller
{
  // Fungsi untuk membuat pengajuan KTA baru
  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'formulir_permohonan' => 'required|file',
      'pernyataan_kebenaran' => 'required|file',
      'pengesahan_menkumham' => 'required|file',
      'akta_pendirian' => 'required|file',
      'akta_perubahan' => 'nullable|file',
      'npwp_perusahaan' => 'required|file',
      'surat_domisili' => 'required|file',
      'ktp_pengurus' => 'required|file',
      'logo' => 'nullable|file',
      'foto_direktur' => 'nullable|file',
      'npwp_pengurus_akta' => 'required|file',
      'bukti_transfer' => 'required|file',
      'rekening_id' => 'required|integer',
      'kabupaten_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
      return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
    }

    try {
      $data = $request->only([
        'kabupaten_id',
        'rekening_id'
      ]);

      $fileFields = [
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
        'bukti_transfer',
        'kabupaten_id',
        'rekening_id'
      ];

      foreach ($fileFields as $field) {
        if ($request->hasFile($field)) {
          $data[$field] = $request->file($field)->store("documents/{$field}", 'public');
        }
      }

      $data['user_id'] = Auth::id();

      $kta = KTA::create($data);

      return response()->json(['message' => 'Pengajuan KTA berhasil disubmit', 'kta' => $kta], 201);
    } catch (\Exception $e) {
      return response()->json(['message' => 'Terjadi kesalahan pada server', 'error' => $e->getMessage()], 500);
    }
  }

  // Fungsi untuk menghapus file yang terkait dengan KTA jika ditolak
  protected function deleteFiles($kta)
  {
    $fileFields = [
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
      'bukti_transfer',
      'kabupaten_id',
      'rekening_id'
    ];

    foreach ($fileFields as $field) {
      if ($kta->$field && Storage::disk('public')->exists($kta->$field)) {
        Storage::disk('public')->delete($kta->$field);
      }
    }
  }

  // Fungsi untuk memperpanjang KTA
  public function extend(Request $request, $id)
  {
    $request->validate([
      'bukti_transfer' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    // Cari KTA berdasarkan ID, atau beri respon jika tidak ditemukan
    $kta = KTA::findOrFail($id);

    if ($request->hasFile('bukti_transfer')) {
      // Hapus file lama jika ada
      if ($kta->bukti_transfer && Storage::disk('public')->exists($kta->bukti_transfer)) {
        Storage::disk('public')->delete($kta->bukti_transfer);
      }

      // Simpan file baru
      $filename = $request->file('bukti_transfer')->store('uploads/bukti_transfer', 'public');

      // Update data KTA
      $kta->update([
        'bukti_transfer' => $filename,
        'status_perpanjangan_kta' => 'pending',
      ]);
    }

    return response()->json(['message' => 'Perpanjangan KTA berhasil diajukan.'], 200);
  }

  public function approveOrReject(Request $request, $id)
  {
    $request->validate([
      'status_perpanjangan_kta' => 'required|in:accepted,rejected',
      'komentar' => 'nullable|string'
    ]);

    $kta = KTA::findOrFail($id);

    if ($request->status_perpanjangan_kta === 'accepted') {
      $kta->status_perpanjangan_kta = 'active';
      $kta->tanggal_diterima = now(); // Tanggal diperpanjang
    } else if (
      $request->status_perpanjangan_kta === 'rejected'
    ) {
      $kta->status_perpanjangan_kta = 'rejected';
      $kta->komentar = $request->komentar; // Simpan komentar penolakan dari admin
    }

    $kta->save();

    return response()->json([
      'message' => 'Status perpanjangan KTA berhasil diperbarui.',
      'kta' => $kta
    ]);
  }

  public function approveKTA($id)
  {
    try {
      // Cari pendaftaran KTA berdasarkan ID
      $registration = KTA::findOrFail($id);

      // Periksa apakah status sudah "approved"
      if ($registration->status === 'accepted') {
        return response()->json([
          'success' => false,
          'message' => 'Pendaftaran KTA sudah disetujui sebelumnya.',
        ], 400);
      }

      // Ubah status menjadi "approved"
      $registration->status = 'accepted';
      $registration->save();

      return response()->json([
        'success' => true,
        'message' => 'Pendaftaran KTA berhasil disetujui.',
        'data' => $registration,
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Terjadi kesalahan saat menyetujui pendaftaran KTA.',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  // Fungsi untuk mendapatkan semua data KTA
  public function index()
  {
    $ktas = KTA::with('user')->get();
    return response()->json($ktas);
  }

  // Fungsi untuk mendapatkan detail KTA berdasarkan ID
  public function show($id)
  {
    $kta = KTA::find($id);

    if (!$kta) {
      return response()->json(['message' => 'KTA not found'], 404);
    }

    return response()->json($kta, 200);
  }

  public function search(Request $request)
  {
    $searchTerm = $request->input('search'); // Ambil input pencarian

    // Query untuk mencari KTA berdasarkan relasi user
    $ktas = KTA::where('status', 'accepted') // Pastikan status KTA adalah accepted
      ->whereHas('user', function ($query) use ($searchTerm) {
        $query->where('nama_perusahaan', 'like', '%' . $searchTerm . '%')
          ->orWhere('alamat_perusahaan', 'like', '%' . $searchTerm . '%')
          ->orWhere('email', 'like', '%' . $searchTerm . '%');
      })
      ->get(); // Ambil data KTA yang memenuhi kriteria pencarian

    // Jika tidak ada hasil pencarian
    if ($ktas->isEmpty()) {
      return response()->json([
        'success' => false,
        'message' => 'KTA tidak ditemukan.'
      ], 404); // Kembalikan 404 jika tidak ada hasil
    }

    return response()->json([
      'success' => true,
      'data' => $ktas
    ], 200); // Kembalikan hasil pencarian
  }
}
