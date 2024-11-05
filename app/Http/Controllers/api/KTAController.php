<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KTA;
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
      'kabupaten_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
      return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
    }

    try {
      $data = $request->only([
        'kabupaten_id'
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
        'bukti_transfer'
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

  // Fungsi untuk memperbarui status KTA
  public function update(Request $request, KTA $kta)
  {
    $validator = Validator::make($request->all(), [
      'status' => 'nullable|in:accepted,rejected',
      'status_perpanjangan_kta' => 'nullable|in:active,inactive,pending,rejected',
      'komentar' => 'nullable|string',
    ]);

    if ($validator->fails()) {
      return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
    }

    if ($request->status === 'rejected') {
      $this->deleteFiles($kta);
      $kta->delete();

      return response()->json(['message' => 'Pendaftaran KTA ditolak dan data dihapus.'], 200);
    }

    if ($request->status === 'accepted') {
      $kta->update([
        'status' => 'accepted',
        'tanggal_diterima' => now(),
        'komentar' => null,
      ]);
    } elseif ($request->has('status_perpanjangan_kta')) {
      $kta->update([
        'status_perpanjangan_kta' => $request->status_perpanjangan_kta,
        'komentar' => $request->status_perpanjangan_kta === 'rejected' ? $request->komentar : null,
      ]);
    }

    return response()->json(['message' => 'Status KTA berhasil diperbarui.', 'kta' => $kta]);
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
      'bukti_transfer'
    ];

    foreach ($fileFields as $field) {
      if ($kta->$field && Storage::disk('public')->exists($kta->$field)) {
        Storage::disk('public')->delete($kta->$field);
      }
    }
  }

  // Fungsi untuk memperpanjang KTA
  public function extend(
    Request $request,
    $id
  ) {
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

  // Fungsi untuk memeriksa masa aktif KTA
  public function checkExpiry()
  {
    $ktas = KTA::where('status', 'accepted')->get();

    foreach ($ktas as $kta) {
      if ($kta->tanggal_diterima && Carbon::parse($kta->tanggal_diterima)->addYear()->isPast()) {
        $kta->update(['status_perpanjangan_kta' => 'inactive']);
      }
    }

    return response()->json(['message' => 'Masa aktif KTA diperiksa dan diperbarui.']);
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
}
