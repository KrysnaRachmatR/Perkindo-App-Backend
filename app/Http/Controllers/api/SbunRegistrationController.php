<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SbunRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Stechstudio\ZipStream\ZipStream;
use ZipArchive;

class SbunRegistrationController extends Controller
{

  public function store(Request $request)
  {
    try {
      // Validasi data
      $validator = Validator::make($request->all(), [
        'akta_pendirian' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'npwp_perusahaan' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'ktp_penanggung_jawab' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'nomor_hp_penanggung_jawab' => 'required|numeric',
        'ktp_pemegang_saham' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'npwp_pemegang_saham' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'email_perusahaan' => 'required|email',
        'logo_perusahaan' => 'required|file|mimes:jpg,png|max:2048',
        'non_konstruksi_klasifikasi_id' => 'required|integer|exists:non_konstruksi_klasifikasis,id',
        'non_konstruksi_sub_klasifikasi_id' => 'required|integer|exists:non_konstruksi_sub_klasifikasis,id',
        'bukti_transfer' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'rekening_id' => 'required|integer|exists:rekening_tujuan,id',
      ]);

      if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
      }

      // Persiapkan folder penyimpanan
      $userId = auth()->id();
      $namaPerusahaan = $request->input('email_perusahaan');
      $folderPath = "sbun/{$userId}/{$namaPerusahaan}";

      // Proses upload file
      $fileFields = [
        'akta_pendirian',
        'npwp_perusahaan',
        'ktp_penanggung_jawab',
        'ktp_pemegang_saham',
        'npwp_pemegang_saham',
        'logo_perusahaan',
        'bukti_transfer',
      ];

      $uploadedFiles = [];
      foreach ($fileFields as $field) {
        if ($request->hasFile($field)) {
          $uploadedFiles[$field] = $request->file($field)->storeAs(
            $folderPath,
            "{$field}_" . time() . '.' . $request->file($field)->extension(),
            'local'
          );
        }
      }

      // Gabungkan data request dengan file yang diunggah
      $data = array_merge(
        $request->only([
          'nomor_hp_penanggung_jawab',
          'email_perusahaan',
          'non_konstruksi_klasifikasi_id',
          'non_konstruksi_sub_klasifikasi_id',
          'rekening_id',
        ]),
        $uploadedFiles
      );

      $data['user_id'] = auth()->id();

      // Simpan data ke database
      $registration = SbunRegistration::create($data);

      return response()->json([
        'message' => 'Pendaftaran SBUN berhasil',
        'data' => $registration,
      ], 201);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Terjadi kesalahan',
        'error' => $e->getMessage(),
      ], 500);
    }
  }




  public function show($id)
  {
    // Menampilkan pendaftaran berdasarkan ID
    $registration = SbunRegistration::with(['nonKonstruksiKlasifikasi', 'nonKonstruksiSubKlasifikasi', 'user'])->find($id);

    if (!$registration) {
      return response()->json(['message' => 'Pendaftaran tidak ditemukan'], 404);
    }

    return response()->json($registration);
  }

  public function status(Request $request, $id)
  {
    // Validasi input untuk status pendaftaran
    $validator = Validator::make($request->all(), [
      'status_diterima' => 'required|in:approve,rejected,pending',
      'komentar' => 'required_if:status_diterima,rejected|string',
    ]);

    // Jika validasi gagal, kembalikan error
    if ($validator->fails()) {
      return response()->json($validator->errors(), 422);
    }

    // Menemukan pendaftaran berdasarkan ID
    $registration = SbunRegistration::findOrFail($id);

    // Jika status expired, set status_aktif menjadi inactive
    if ($registration->expired_at && $registration->expired_at->isPast()) {
      $registration->status_aktif = 'expired';
    }

    // Proses jika status ditolak (rejected)
    if ($request->status_diterima === 'rejected') {
      // Validasi komentar saat status ditolak
      if (empty($request->komentar)) {
        return response()->json(['message' => 'Komentar diperlukan untuk status ditolak.'], 422);
      }

      // Daftar file yang harus dihapus jika ditolak
      $fileFields = [
        'akta_pendirian',
        'npwp_perusahaan',
        'ktp_penanggung_jawab',
        'npwp_penanggung_jawab',
        'foto_penanggung_jawab',
        'ktp_pemegang_saham',
        'npwp_pemegang_saham',
        'logo_perusahaan',
      ];

      // Menghapus file dari storage jika status ditolak
      foreach ($fileFields as $field) {
        if ($registration->$field) {
          Storage::disk('public')->delete($registration->$field);
        }
      }

      // Menghapus data pendaftaran jika ditolak
      $registration->delete();

      return response()->json(['message' => 'Pendaftaran berhasil dihapus'], 200);
    } else {
      // Update status jika disetujui
      $registration->update([
        'status_diterima' => 'approve',  // Status disetujui
        'komentar' => $request->komentar ?? null,  // Menyimpan komentar admin jika ada
        'status_aktif' => 'active',
        'tanggal_diterima' => now(), // Menyimpan tanggal diterima saat disetujui
        'expired_at' => now()->addYears(2), // Masa aktif 2 tahun
      ]);

      return response()->json([
        'message' => 'Pendaftaran berhasil disetujui.',
        'registration' => $registration->load(['user', 'nonKonstruksiKlasifikasi', 'nonKonstruksiSubKlasifikasi']),
      ], 200);
    }
  }



  public function downloadSBUNDocuments($id)
  {
    try {
      // Cari data registrasi berdasarkan ID
      $registration = SbunRegistration::findOrFail($id);

      // Tentukan nama folder berdasarkan user_id dan email_perusahaan
      $userId = $registration->user_id;
      $namaPerusahaan = preg_replace('/[^A-Za-z0-9\-]/', '_', $registration->email_perusahaan);
      $folderPath = "sbun/{$userId}_{$namaPerusahaan}";

      // Daftar file yang akan dimasukkan ke dalam ZIP
      $fileFields = [
        'akta_pendirian',
        'npwp_perusahaan',
        'ktp_penanggung_jawab',
        'ktp_pemegang_saham',
        'npwp_pemegang_saham',
        'logo_perusahaan',
        'bukti_transfer',
      ];

      // Membuat objek ZIP
      $zip = new ZipArchive();
      $zipFileName = "sbun_{$registration->id}_documents.zip";
      $zipFilePath = storage_path("app/public/{$zipFileName}");

      if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
        return response()->json(['message' => 'Gagal membuat file ZIP'], 500);
      }

      // Tambahkan file ke dalam ZIP
      foreach ($fileFields as $field) {
        if ($registration->$field) {
          $filePath = storage_path("app/{$registration->$field}");
          if (file_exists($filePath)) {
            $zip->addFile($filePath, basename($filePath));
          }
        }
      }

      // Tutup file ZIP
      $zip->close();

      // Kirim file ZIP ke pengguna dan hapus setelah diunduh
      return response()->download($zipFilePath)->deleteFileAfterSend(true);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Terjadi kesalahan saat mengunduh dokumen',
        'error' => $e->getMessage(),
      ], 500);
    }
  }


  public function index()
  {
    // Menampilkan daftar pendaftaran SBUN
    $registrations = SbunRegistration::with('user', 'nonKonstruksiKlasifikasi', 'nonKonstruksiSubKlasifikasi')->get();
    return response()->json($registrations);
  }

  public function search(Request $request)
  {
    $searchTerm = $request->input('search');

    $registrations = SbunRegistration::where('approval_status', 'approved')
      ->whereHas('user', function ($query) use ($searchTerm) {
        $query->where('nama_perusahaan', 'like', '%' . $searchTerm . '%')
          ->orWhere('email', 'like', '%' . $searchTerm . '%');
      })
      ->get();

    if ($registrations->isEmpty()) {
      return response()->json(['message' => 'SBU tidak ditemukan.'], 404);
    }

    return response()->json($registrations, 200);
  }
}
