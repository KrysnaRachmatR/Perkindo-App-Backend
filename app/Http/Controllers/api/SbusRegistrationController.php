<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SBUSRegistrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class SbusRegistrationController extends Controller
{
  public function store(Request $request)
  {
    try {
      // Validasi data
      $validator = Validator::make($request->all(), [
        'akta_asosiasi_aktif_masa_berlaku' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'akta_perusahaan_pendirian' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'akta_perubahan' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'pengesahan_menkumham' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'nib_berbasis_resiko' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'ktp_pengurus' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'npwp_pengurus' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'SKK' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'ijazah_legalisir' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'PJTBU' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'PJKSBU' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'email_perusahaan' => 'required|email',
        'kop_perusahaan' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'nomor_hp_penanggung_jawab' => 'required|numeric',
        'foto_pas_direktur' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'surat_pernyataan_penanggung_jawab_mutlak' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'surat_pernyataan_SMAP' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'lampiran_TKK' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'neraca_keuangan_2_tahun_terakhir' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'akun_OSS' => 'required|file|mimes:jpg,png,pdf|max:2048',
        'rekening_id' => 'required|integer|exists:rekening_tujuan,id',
        'bukti_transfer' => 'required|file|mimes:jpg,png,pdf|max:2048',
      ]);

      if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
      }

      // Persiapkan folder penyimpanan
      $userId = auth()->id();
      $namaPerusahaan = $request->input('email_perusahaan');
      $folderPath = "sbus/{$userId}/{$namaPerusahaan}";

      // Proses upload file
      $fileFields = [
        'akta_asosiasi_aktif_masa_berlaku',
        'akta_perusahaan_pendirian',
        'akta_perubahan',
        'pengesahan_menkumham',
        'nib_berbasis_resiko',
        'ktp_pengurus',
        'npwp_pengurus',
        'SKK',
        'ijazah_legalisir',
        'PJTBU',
        'PJKSBU',
        'kop_perusahaan',
        'foto_pas_direktur',
        'surat_pernyataan_penanggung_jawab_mutlak',
        'surat_pernyataan_SMAP',
        'lampiran_TKK',
        'neraca_keuangan_2_tahun_terakhir',
        'akun_OSS',
        'bukti_transfer'
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
          'rekening_id',
          'konstruksi_klasifikasi_id',
          'konstruksi_sub_klasifikasi_id'
        ]),
        $uploadedFiles
      );

      $data['user_id'] = $userId;

      // Simpan data ke database
      $registration = SBUSRegistrations::create($data);

      return response()->json([
        'message' => 'Pendaftaran SBU berhasil',
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
    $registration = SBUSRegistrations::with(['KonstruksiKlasifikasi', 'KonstruksiSubKlasifikasi', 'user'])->find($id);

    if (!$registration) {
      return response()->json(['message' => 'Pendaftaran tidak ditemukan'], 404);
    }

    return response()->json($registration);
  }

  public function index()
  {
    // Menampilkan daftar pendaftaran SBUS
    $registrations = SBUSRegistrations::with('user', 'KonstruksiKlasifikasi', 'KonstruksiSubKlasifikasi')->get();
    return response()->json($registrations);
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
    $registration = SBUSRegistrations::findOrFail($id);

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
        'akta_asosiasi_aktif_masa_berlaku',
        'akta_perusahaan_pendirian',
        'akta_perubahan',
        'pengesahan_menkumham',
        'nib_berbasis_resiko',
        'ktp_pengurus',
        'npwp_pengurus',
        'SKK',
        'ijazah_legalisir',
        'PJTBU',
        'PJKSBU',
        'kop_perusahaan',
        'foto_pas_direktur',
        'surat_pernyataan_penanggung_jawab_mutlak',
        'surat_pernyataan_SMAP',
        'lampiran_TKK',
        'neraca_keuangan_2_tahun_terakhir',
        'akun_OSS',
        'bukti_transfer'
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
        'registration' => $registration->load(['user', 'KonstruksiKlasifikasi', 'KonstruksiSubKlasifikasi']),
      ], 200);
    }
  }

  public function downloadSBUSDocuments($id)
  {
    try {
      // Cari data registrasi berdasarkan ID
      $registration = SBUSRegistrations::findOrFail($id);

      // Tentukan nama folder berdasarkan user_id dan email_perusahaan
      $userId = $registration->user_id;
      $namaPerusahaan = preg_replace('/[^A-Za-z0-9\-]/', '_', $registration->email_perusahaan);
      $folderPath = "sbun/{$userId}_{$namaPerusahaan}";

      // Daftar file yang akan dimasukkan ke dalam ZIP
      $fileFields = [
        'akta_asosiasi_aktif_masa_berlaku',
        'akta_perusahaan_pendirian',
        'akta_perubahan',
        'pengesahan_menkumham',
        'nib_berbasis_resiko',
        'ktp_pengurus',
        'npwp_pengurus',
        'SKK',
        'ijazah_legalisir',
        'PJTBU',
        'PJKSBU',
        'kop_perusahaan',
        'foto_pas_direktur',
        'surat_pernyataan_penanggung_jawab_mutlak',
        'surat_pernyataan_SMAP',
        'lampiran_TKK',
        'neraca_keuangan_2_tahun_terakhir',
        'akun_OSS',
        'bukti_transfer'
      ];

      // Membuat objek ZIP
      $zip = new ZipArchive();
      $zipFileName = "sbus_{$registration->id}_documents.zip";
      $zipFilePath = storage_path("app/public/{$zipFileName}");

      if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
        return response()->json(['message' => 'Gagal membuat file ZIP'], 500);
      }

      // Tambahkan file ke dalam ZIP jika file ada
      foreach ($fileFields as $field) {
        $filePath = storage_path("app/{$registration->$field}");

        if ($registration->$field && file_exists($filePath)) {
          // Menambahkan file ke dalam ZIP dengan nama yang sesuai
          $zip->addFile($filePath, basename($filePath));
        }
      }

      // Tutup file ZIP
      $zip->close();

      // Periksa jika ZIP berhasil dibuat dan kirim file ZIP ke pengguna
      if (!file_exists($zipFilePath)) {
        return response()->json(['message' => 'File ZIP tidak ditemukan'], 500);
      }

      // Kirim file ZIP ke pengguna dan hapus setelah diunduh
      return response()->download($zipFilePath)->deleteFileAfterSend(true);
    } catch (\Exception $e) {
      // Tangani kesalahan dan kirimkan informasi kesalahan
      return response()->json([
        'message' => 'Terjadi kesalahan saat mengunduh dokumen',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  public function search(Request $request)
  {
    try {
      $searchTerm = $request->input('search');

      // Validasi input pencarian
      if (!$searchTerm) {
        return response()->json(['message' => 'Parameter pencarian tidak diberikan.'], 400);
      }

      // Cari registrasi yang diterima dan filter berdasarkan nama perusahaan atau email
      $registrations = SBUSRegistrations::where('status_aktif', 'active')
        ->whereHas('user', function ($query) use ($searchTerm) {
          $query->where('nama_perusahaan', 'like', '%' . $searchTerm . '%')
            ->orWhere('email', 'like', '%' . $searchTerm . '%');
        })
        ->with('user') // Mengambil relasi user untuk data yang lebih lengkap
        ->get();

      if ($registrations->isEmpty()) {
        return response()->json(['message' => 'SBU tidak ditemukan.'], 404);
      }

      // Mengembalikan data registrasi dalam format JSON
      return response()->json([
        'message' => 'Data ditemukan.',
        'data' => $registrations
      ], 200);
    } catch (\Exception $e) {
      // Menangani error jika terjadi kesalahan
      return response()->json([
        'message' => 'Terjadi kesalahan.',
        'error' => $e->getMessage(),
      ], 500);
    }
  }
}
