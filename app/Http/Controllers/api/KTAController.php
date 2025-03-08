<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KTA;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use ZipStream\ZipStream;

class KtaController extends Controller
{
  // ----------USER CONTROLLER----------\\
public function store(Request $request)
{
      // Validasi input
      $validator = Validator::make($request->all(), [
          'akta_pendirian' => 'required|file',
          'npwp_perusahaan' => 'required|file',
          'nib' => 'nullable|file',
          'pjbu' => 'nullable|file',
          'data_pengurus_pemegang_saham' => 'nullable|file',
          'alamat_email_badan_usaha' => 'required|email',
          'kabupaten_id' => 'required|integer',
          'rekening_id' => 'required|integer',
          'bukti_transfer' => 'required|file',
          'logo_badan_usaha' => 'nullable|file'
      ]);

      if ($validator->fails()) {
          return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
      }

      try {
          $userId = Auth::id();
          $basePath = "kta/{$userId}"; // Direktori penyimpanan berdasarkan user_id

          // Daftar file yang akan disimpan
          $fileFields = [
              'akta_pendirian',
              'npwp_perusahaan',
              'nib',
              'pjbu',
              'data_pengurus_pemegang_saham',
              'bukti_transfer',
              'logo_badan_usaha'
          ];

          $data = $request->only([
              'kabupaten_id',
              'rekening_id',
              'alamat_email_badan_usaha'
          ]);

          // Menyimpan file yang diunggah
          foreach ($fileFields as $field) {
              if ($request->hasFile($field)) {
                  $originalName = $request->file($field)->getClientOriginalName();
                  $data[$field] = $request->file($field)->storeAs($basePath, $originalName, 'local');
              }
          }

          // Menambahkan user_id ke data
          $data['user_id'] = $userId;

          // Mengecek apakah pengguna memiliki KTA yang ditolak sebelumnya
          $existingKTA = KTA::where('user_id', $userId)
              ->where('status_diterima', 'rejected')
              ->first();

          if ($existingKTA) {
              // Jika ada KTA yang ditolak, update data
              $existingKTA->update($data);
              $message = 'Pengajuan KTA diperbarui setelah penolakan.';
          } else {
              // Jika tidak ada, buat data KTA baru
              $data['status_diterima'] = 'pending';
              $data['status_aktif'] = 'will_expire';
              $data['tanggal_diterima'] = null;
              $data['expired_at'] = null;
              $data['status_perpanjangan_kta'] = null;
              $data['komentar'] = null;
              $data['can_reapply'] = false;
              $data['rejection_reason'] = null;
              $data['rejection_date'] = null;

              $existingKTA = KTA::create($data);
              $message = 'Pengajuan KTA berhasil dikirim.';
          }

          return response()->json(['message' => $message, 'kta' => $existingKTA], 201);
      } catch (\Exception $e) {
          return response()->json(['message' => 'Terjadi kesalahan pada server', 'error' => $e->getMessage()], 500);
      }
  }

public function extend(Request $request, $id)
{
    $request->validate([
      'bukti_transfer' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    // Cari KTA berdasarkan ID
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

    return response()->json(['message' => 'KTA extension submitted successfully.'], 200);
  }


  // 
  //*//
  //*//
  //*//
  //*//
  //*//
  //*//
  //*//
  //

  // ----------ADMIN CONTROLLER----------\\

  // Fungsi untuk menyetujui KTA
public function approveKTA(Request $request, $id)
{
    try {
      // Validasi input
      $validated = $request->validate([
        'status_diterima' => 'required|in:approve,rejected',
        'komentar' => 'nullable|string|max:255', // Komentar maksimal 255 karakter
      ]);

      // Cari pendaftaran KTA berdasarkan ID
      $ktaRegistration = KTA::find($id);

      // Jika pendaftaran KTA tidak ditemukan
      if (!$ktaRegistration) {
        return response()->json([
          'success' => false,
          'message' => 'KTA registration not found.',
        ], 404);
      }

      // Proses jika status adalah "approve"
      if ($validated['status_diterima'] === 'approve') {
        // Periksa apakah sudah disetujui sebelumnya
        if ($ktaRegistration->status_diterima === 'approve') {
          return response()->json([
            'success' => false,
            'message' => 'KTA registration has been previously approved.',
          ], 400);
        }

        // Perbarui status ke "approve"
        $ktaRegistration->status_diterima = 'approve';
        $ktaRegistration->tanggal_diterima = now();
        $ktaRegistration->status_aktif = 'active';
        $ktaRegistration->expired_at = now()->addYears(2); // Berlaku 2 tahun
        $ktaRegistration->can_reapply = true; // Membolehkan pengajuan ulang
        $ktaRegistration->komentar = null; // Tidak ada komentar ketika disetujui
        $ktaRegistration->rejection_date = null; // Tidak ada tanggal penolakan
        $ktaRegistration->save();

        return response()->json([
          'success' => true,
          'message' => 'KTA registration has been successfully approved.',
          'data' => $ktaRegistration,
        ], 200);
      }

      // Proses jika status adalah "rejected"
      if ($validated['status_diterima'] === 'rejected') {
        // Cek apakah status sudah aktif dan disetujui
        if (
          $ktaRegistration->status_aktif === 'active' && $ktaRegistration->status_diterima === 'approve'
        ) {
          return response()->json([
            'success' => false,
            'message' => 'KTA registration that has been approved and active cannot be rejected.',
          ], 400);
        }

        // Update status_diterima menjadi "rejected"
        $ktaRegistration->status_diterima = 'rejected';

        // Reset status untuk memungkinkan pendaftaran ulang
        $ktaRegistration->can_reapply = true; // Membolehkan pendaftaran ulang
        $ktaRegistration->komentar = $validated['komentar']; // Simpan komentar (alasan penolakan)
        $ktaRegistration->rejection_date = now(); // Simpan tanggal penolakan

        // Simpan perubahan status
        $ktaRegistration->save();

        // Hapus file yang diunggah oleh user di storage/app/kta/$id_user
        $userId = $ktaRegistration->user_id;
        $userDirectory = storage_path("app/kta/{$userId}");

        // Debugging: Cek apakah direktori ada dan file ditemukan
        if (is_dir($userDirectory)) {
          $files = glob($userDirectory . '/*'); // Dapatkan semua file dalam direktori
          foreach ($files as $file) {
            if (is_file($file)) {
              unlink($file); // Hapus file
            }
          }
          rmdir($userDirectory); // Hapus direktori setelah file dihapus
        }

        return response()->json([
          'success' => true,
          'message' => 'KTA registration has been successfully rejected. Users can immediately re-register. Document deleted.',
        ], 200);
      }
    } catch (\Illuminate\Validation\ValidationException $validationException) {
      // Tangani kesalahan validasi
      return response()->json([
        'success' => false,
        'message' => 'Validation failed.',
        'errors' => $validationException->errors(),
      ], 422);
    } catch (\Exception $exception) {
      // Tangani kesalahan umum
      return response()->json([
        'success' => false,
        'message' => 'An error occurred while updating the KTA registration status.',
        'error' => $exception->getMessage(),
      ], 500);
    }
  }

public function allPending()
{
      try {
          // Query hanya data dengan status_diterima = 'pending'
          $registrants = KTA::select(
              'kta.user_id',
              'kta.id',
              'users.nama_perusahaan',
              'users.nama_direktur',
              'users.alamat_perusahaan',
              'users.email',
              'kta.status_diterima',
              'kta.status_aktif',
              'kta.tanggal_diterima',
              'kta.expired_at',
              'kta.rejection_date',
              'kta.komentar',
              'kta.created_at',
              'kota_kabupaten.nama as kota_kabupaten'
          )
              ->join('users', 'kta.user_id', '=', 'users.id')
              ->join('kota_kabupaten', 'kta.kabupaten_id', '=', 'kota_kabupaten.id')
              ->where('kta.status_diterima', 'pending') // Hanya ambil yang pending
              ->orderBy('kta.created_at', 'desc')
              ->get();
  
          return response()->json([
              'success' => true,
              'message' => 'Data pendaftaran pending berhasil diambil.',
              'data' => $registrants,
          ], 200);
      } catch (\Exception $exception) {
          return response()->json([
              'success' => false,
              'message' => 'Terjadi kesalahan saat mengambil data pendaftaran pending.',
              'error' => $exception->getMessage(),
          ], 500);
      }
  }

public function index()
{
      $ktas = KTA::select(
              'kta.id',
              'users.nama_perusahaan',
              'users.nama_direktur',
              'users.alamat_perusahaan',
              'users.email',
              'kta.logo_badan_usaha as logo',
              'kota_kabupaten.nama as kota_kabupaten',
              'kta.status_aktif',
              'kta.tanggal_diterima',
              'kta.expired_at'
          )
          ->join('users', 'kta.user_id', '=', 'users.id')
          ->join('kota_kabupaten', 'kta.kabupaten_id', '=', 'kota_kabupaten.id')
          ->where('kta.status_diterima', 'approve')
          ->get();
  
      return response()->json($ktas);
  }
  
public function show($id)
{
    $kta = KTA::select(
        'users.nama_perusahaan',
        'users.nama_direktur',
        'users.nama_penanggung_jawab',
        'users.alamat_perusahaan',
        'users.email',
        'kta.logo_badan_usaha as logo',
        'kta.status_aktif',
        'kta.tanggal_diterima',
        'kta.expired_at',
        'kota_kabupaten.nama as kota_kabupaten'
    )
    ->join('users', 'kta.user_id', '=', 'users.id')
    ->join('kota_kabupaten', 'kta.kabupaten_id', '=', 'kota_kabupaten.id')
    ->where('kta.id', $id)
    ->where('kta.status_diterima', 'approve')
    ->first();

    if (!$kta) {
        return response()->json(['message' => 'KTA not found or not approved'], 404);
    }

    return response()->json($kta, 200);
}

public function search(Request $request)
{
    $searchTerm = $request->input('search'); // Ambil input pencarian

    $ktas = KTA::select(
        'kta.id',
        'users.nama_perusahaan',
        'users.nama_direktur',
        'users.alamat_perusahaan',
        'users.email',
        'kta.logo_badan_usaha as logo',
        'kta.status_aktif',
        'kta.tanggal_diterima',
        'kta.expired_at',
        'kota_kabupaten.nama as kota_kabupaten'
    )
    ->join('users', 'kta.user_id', '=', 'users.id')
    ->join('kota_kabupaten', 'kta.kabupaten_id', '=', 'kota_kabupaten.id')
    ->where(function ($query) use ($searchTerm) {
        $query->where('users.nama_perusahaan', 'like', '%' . $searchTerm . '%')
            ->orWhere('users.alamat_perusahaan', 'like', '%' . $searchTerm . '%')
            ->orWhere('users.email', 'like', '%' . $searchTerm . '%')
            ->orWhere('kta.status_aktif', 'like', '%' . $searchTerm . '%');
    })
    ->where('kta.status_diterima', 'approve')
    ->get(); 

    if ($ktas->isEmpty()) {
        return response()->json(['message' => 'KTA not found'], 404);
    }

    return response()->json($ktas, 200);
}

public function downloadKTAFiles($id)
{
    try {
      // Lokasi folder file KTA berdasarkan ID KTA
      $directoryPath = "kta/{$id}"; // Menggunakan ktaId untuk folder path

      // Verifikasi apakah folder KTA ada di storage lokal
      if (!Storage::disk('local')->exists($directoryPath)) {
        return response()->json([
          'success' => false,
          'message' => 'The file for this KTA was not found.',
        ], 404);
      }

      // Ambil semua file dalam folder KTA
      $files = Storage::disk('local')->files($directoryPath);

      // Jika folder tidak mengandung file
      if (empty($files)) {
        return response()->json([
          'success' => false,
          'message' => 'The folder contains no files.',
        ], 404);
      }

      // Nama file ZIP
      $zipFileName = "kta_files_{$id}.zip";

      // Membuat ZIP dan streaming ke browser
      return response()->stream(function () use ($files) {
        try {
          // Membuat objek ZipStream
          $zip = new ZipStream();

          foreach ($files as $file) {
            // Mendapatkan path file di storage lokal
            $filePath = storage_path("app/{$file}");

            // Validasi apakah file ada
            if (!file_exists($filePath)) {
              Log::warning("File tidak ditemukan: {$filePath}");
              continue; // Skip jika file tidak ada
            }

            // Tambahkan file ke ZIP (pastikan nama file tanpa path penuh)
            $zip->addFileFromPath(basename($file), $filePath);
          }

          // Menyelesaikan proses ZIP
          $zip->finish();
        } catch (\Exception $e) {
          Log::error('Error creating ZIP file: ' . $e->getMessage());
          throw $e; // Throw exception untuk ditangani oleh blok catch utama
        }
      }, 200, [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
      ]);
    } catch (\Exception $e) {
      // Log kesalahan dan kembalikan response error
      Log::error('Error downloading KTA files for KTA ' . $id . ': ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'The file for this KTA was not found.',
      ], 404);
    }
  }

  public function uploadKta(Request $request, $id)
{
    // Menemukan data KTA berdasarkan ID
    $kta = KTA::findOrFail($id);

    // Validasi file yang diunggah (hanya menerima PDF/JPG/PNG dan max 2MB)
    $validator = Validator::make($request->all(), [
        'kta_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'File tidak valid.',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        if ($request->hasFile('kta_file')) {
            $file = $request->file('kta_file');

            // Folder penyimpanan berdasarkan user_id
            $folderPath = "kta/{$id}";

            // Membuat nama file unik agar tidak tertimpa
            $fileName = 'kta_' . time() . '.' . $file->getClientOriginalExtension();

            // Simpan file ke dalam storage -> app -> kta -> user_id
            $filePath = $file->storeAs($folderPath, $fileName);

            // Simpan path ke database (tanpa 'storage/app')
            $kta->update([
                'kta_file' => $filePath,
            ]);

            return response()->json([
                'message' => 'KTA uploaded successfully',
                'file_path' => $filePath
            ]);
        } else {
            return response()->json([
                'message' => 'No file uploaded.'
            ], 400);
        }
    } catch (\Exception $e) {
        Log::error('Error uploading KTA file for ID ' . $id . ': ' . $e->getMessage());
        return response()->json([
            'message' => 'An error occurred while uploading the file.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
