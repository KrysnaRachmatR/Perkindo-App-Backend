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
use ZipArchive;


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
  
          // **Cek apakah user sudah memiliki KTA yang masih aktif atau approved**
          $existingKTA = KTA::where('user_id', $userId)
              ->whereIn('status_diterima', ['approved', 'pending'])
              ->where(function ($query) {
                  $query->where('status_aktif', 'active')
                      ->orWhereNull('expired_at')
                      ->orWhereDate('expired_at', '>', now());
              })
              ->first();
  
          if ($existingKTA) {
              return response()->json([
                  'message' => 'You already have an active KTA or one that is being processed. You can only re-register if the KTA has expired..'
              ], 403);
          }
  
          // Data yang akan disimpan
          $data = $request->only([
              'kabupaten_id',
              'rekening_id',
              'alamat_email_badan_usaha'
          ]);
  
          // **Cek apakah user memiliki KTA yang ditolak**
          $rejectedKTA = KTA::where('user_id', $userId)
              ->where('status_diterima', 'rejected')
              ->first();
  
          if ($rejectedKTA) {
              // **Hapus dokumen lama sebelum update**
              $fileFields = [
                  'akta_pendirian',
                  'npwp_perusahaan',
                  'nib',
                  'pjbu',
                  'data_pengurus_pemegang_saham',
                  'bukti_transfer',
                  'logo_badan_usaha'
              ];
  
              foreach ($fileFields as $field) {
                  if ($rejectedKTA->$field) {
                      Storage::delete($rejectedKTA->$field);
                      $rejectedKTA->$field = null;
                  }
              }
  
              // **Simpan dokumen baru**
              foreach ($fileFields as $field) {
                  if ($request->hasFile($field)) {
                      $originalName = $request->file($field)->getClientOriginalName();
                      $rejectedKTA->$field = $request->file($field)->storeAs($basePath, $originalName, 'local');
                  }
              }
  
              // **Perbarui data KTA yang ditolak**
              $rejectedKTA->update(array_merge($data, [
                  'status_diterima' => 'pending',
                  'status_aktif' => 'will_expire',
                  'tanggal_diterima' => null,
                  'expired_at' => null,
                  'status_perpanjangan_kta' => null,
                  'komentar' => null,
                  'can_reapply' => false,
                  'rejection_reason' => null,
                  'rejection_date' => null
              ]));
  
              return response()->json(['message' => 'KTA application renewed after rejection.', 'kta' => $rejectedKTA], 200);
          }
  
          // **Jika tidak ada KTA yang ditolak, buat entri baru**
          foreach (['akta_pendirian', 'npwp_perusahaan', 'nib', 'pjbu', 'data_pengurus_pemegang_saham', 'bukti_transfer', 'logo_badan_usaha'] as $field) {
              if ($request->hasFile($field)) {
                  $originalName = $request->file($field)->getClientOriginalName();
                  $data[$field] = $request->file($field)->storeAs($basePath, $originalName, 'local');
              }
          }
  
          $data['user_id'] = $userId;
          $data['status_diterima'] = 'pending';
          $data['status_aktif'] = 'will_expire';
          $data['tanggal_diterima'] = null;
          $data['expired_at'] = null;
          $data['status_perpanjangan_kta'] = null;
          $data['komentar'] = null;
          $data['can_reapply'] = false;
          $data['rejection_reason'] = null;
          $data['rejection_date'] = null;
  
          $newKTA = KTA::create($data);
  
          return response()->json(['message' => 'KTA application successfully submitted.', 'kta' => $newKTA], 201);
      } catch (\Exception $e) {
          return response()->json(['message' => 'An error occurred on the server', 'error' => $e->getMessage()], 500);
      }
  }
  
  
  public function extend(Request $request, $id)
{
    $request->validate([
        'bukti_transfer' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $userId = Auth::id(); // Ambil ID user yang sedang login

    // Cari KTA berdasarkan ID dan pastikan KTA milik user yang sedang login
    $kta = KTA::where('id', $id)->where('user_id', $userId)->first();

    if (!$kta) {
        return response()->json(['message' => 'KTA not found or not yours.'], 403);
    }

    // Cek apakah KTA bisa diperpanjang (hanya dalam 1 bulan sebelum expired)
    if (!$kta->expired_at || now()->diffInDays($kta->expired_at, false) > 30) {
        return response()->json(['message' => 'You can only extend your KTA within 1 month before it expires.'], 403);
    }

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
        if (!$ktaRegistration) {
            return response()->json([
                'success' => false,
                'message' => 'KTA registration not found.',
            ], 404);
        }

        // Jika status adalah "approve"
        if ($validated['status_diterima'] === 'approve') {
            if ($ktaRegistration->status_diterima === 'approve') {
                return response()->json([
                    'success' => false,
                    'message' => 'KTA registration has been previously approved.',
                ], 400);
            }

            // Update status KTA menjadi approve
            $ktaRegistration->update([
                'status_diterima' => 'approve',
                'tanggal_diterima' => now(),
                'status_aktif' => 'active',
                'expired_at' => Carbon::now()->addYears(2), // Berlaku 2 tahun
                'can_reapply' => false, // Tidak bisa daftar ulang
                'komentar' => null, 
                'rejection_date' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KTA registration has been successfully approved.',
                'data' => $ktaRegistration,
            ], 200);
        }

        // Jika status adalah "rejected"
        if ($validated['status_diterima'] === 'rejected') {
            if ($ktaRegistration->status_diterima === 'approve' && $ktaRegistration->status_aktif === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'KTA registration that has been approved and active cannot be rejected.',
                ], 400);
            }

            // Ambil user_id untuk menghapus dokumen
            $userId = $ktaRegistration->user_id;
            $userDirectory = storage_path("app/kta/{$userId}");

            // Hapus file yang diunggah
            if (is_dir($userDirectory)) {
                $files = glob($userDirectory . '/*'); 
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file); // Hapus file
                    }
                }
                rmdir($userDirectory); // Hapus direktori jika kosong
            }

            // Reset path file di database (set null)
            $ktaRegistration->update([
                'status_diterima' => 'rejected',
                'status_aktif' => null,
                'komentar' => $validated['komentar'],
                'rejection_date' => now(),
                'can_reapply' => true, // Bisa daftar ulang
                'akta_pendirian' => null,
                'npwp_perusahaan' => null,
                'nib' => null,
                'pjbu' => null,
                'data_pengurus_pemegang_saham' => null,
                'bukti_transfer' => null,
                'logo_badan_usaha' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'KTA registration has been rejected. Documents have been deleted.',
            ], 200);
        }
    } catch (\Illuminate\Validation\ValidationException $validationException) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validationException->errors(),
        ], 422);
    } catch (\Exception $exception) {
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
              'users.nama_penanggung_jawab',
              'users.alamat_perusahaan',
              'users.email',
              'kta.kabupaten_id',
              'kota_kabupaten.nama as kota_kabupaten',
              'kta.status_aktif',
              'kta.status_diterima',
              'kta.status_perpanjangan_kta',
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
          'kta.id',
          'users.nama_perusahaan',
          'users.nama_direktur',
          'users.nama_penanggung_jawab',
          'users.alamat_perusahaan',
          'users.email',
          'kta.kabupaten_id',
          'kota_kabupaten.nama as kota_kabupaten',
          'kta.status_aktif',
          'kta.status_diterima',
          'kta.status_perpanjangan_kta',
          'kta.tanggal_diterima',
          'kta.expired_at'
      )
      ->join('users', 'kta.user_id', '=', 'users.id')
      ->join('kota_kabupaten', 'kta.kabupaten_id', '=', 'kota_kabupaten.id')
      ->where('kta.id', $id) // Mencari berdasarkan ID
      ->where('kta.status_diterima', 'approve') // Hanya yang disetujui
      ->first(); // Mengambil satu data saja
  
      if (!$kta) {
          return response()->json(['message' => 'KTA not found or not approved'], 404);
      }
  
      return response()->json($kta, 200);
  }
  
  public function search(Request $request)
  {
      $searchTerm = $request->input('search');
  
      if (!$searchTerm) {
          return response()->json(['message' => 'Search term is required'], 400);
      }
  
      $ktas = KTA::select(
          'kta.id',
          'users.nama_perusahaan',
          'users.nama_direktur',
          'users.alamat_perusahaan',
          'users.email',
          'kta.status_aktif',
          'kta.tanggal_diterima',
          'kta.expired_at',
          'kota_kabupaten.nama as kota_kabupaten'
      )
      ->join('users', 'kta.user_id', '=', 'users.id')
      ->join('kota_kabupaten', 'kta.kabupaten_id', '=', 'kota_kabupaten.id')
      ->where('kta.status_diterima', 'approve')
      ->where(function ($query) use ($searchTerm) {
          $query->where('users.nama_perusahaan', 'like', "%$searchTerm%")
              ->orWhere('users.alamat_perusahaan', 'like', "%$searchTerm%")
              ->orWhere('users.email', 'like', "%$searchTerm%");
      })
      ->get(); 
  
      if ($ktas->isEmpty()) {
          return response()->json(['message' => 'KTA not found'], 404);
      }
  
      return response()->json($ktas, 200);
  }
  

  public function uploadKta(Request $request, $userId)
{
    $user = User::findOrFail($userId);

    // Validasi file
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
            
            // Simpan berdasarkan ID user
            $folderPath = "kta(CardFromDPP)/{$userId}";
            $fileName = 'kta_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs($folderPath, $fileName, 'public'); // Simpan ke storage/public/kta/{userId}

            // Update atau buat KTA baru untuk user
            $kta = KTA::updateOrCreate(
                ['user_id' => $userId], // Cek apakah KTA user sudah ada
                ['kta_file' => $filePath] // Simpan file path
            );

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
        Log::error('Error uploading KTA file for user ID ' . $userId . ': ' . $e->getMessage());
        return response()->json([
            'message' => 'An error occurred while uploading the file.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
