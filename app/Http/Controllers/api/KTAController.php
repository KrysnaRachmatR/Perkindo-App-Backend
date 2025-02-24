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
    $validator = Validator::make(
      $request->all(),
      [
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
      ]
    );

    // Jika validasi gagal
    if ($validator->fails()) {
      return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
    }

    try {
      // Mengambil data yang diperlukan dari request
      $data = $request->only([
        'kabupaten_id',
        'rekening_id',
      ]);

      // Menentukan direktori berdasarkan user_id
      $userId = Auth::id();
      $basePath = "kta/{$userId}"; // Path untuk user

      // Daftar field yang menyimpan file
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

      // Memeriksa dan menyimpan file yang ada
      foreach ($fileFields as $field) {
        if ($request->hasFile($field)) {
          // Menyimpan file dengan nama asli ke dalam folder user_id di disk storage
          $originalName = $request->file($field)->getClientOriginalName();
          $data[$field] = $request->file($field)->storeAs($basePath, $originalName, 'local');
        }
      }

      // Menambahkan user_id ke data yang akan disimpan
      $data['user_id'] = $userId;

      // Mengecek apakah pengguna sudah memiliki pendaftaran KTA sebelumnya yang statusnya ditolak
      $existingKTA = KTA::where('user_id', $userId)->where('status_diterima', 'rejected')->first();

      if ($existingKTA) {
        // Jika ada pendaftaran KTA yang ditolak, update data yang ada
        $existingKTA->update($data);
        $message = 'The KTA application is updated after rejection.';
      } else {
        // Jika tidak ada pendaftaran yang ditolak sebelumnya, buat data KTA baru
        $existingKTA = KTA::create($data);
        $message = 'The KTA application has been successfully submitted.';
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

  public function allPending(Request $request)
  {
    try {
      // Ambil parameter filter opsional dari request
      $status = $request->query('status', 'pending', 'rejected'); // 'null' untuk melihat semua status

      // Query data pendaftaran
      $query = KTA::select(
        'ktas.user_id',
        'ktas.id',
        'users.nama_perusahaan',
        'users.nama_direktur',
        'users.alamat_perusahaan',
        'users.email',
        'ktas.status_diterima',
        'ktas.status_aktif',
        'ktas.tanggal_diterima',
        'ktas.expired_at',
        'ktas.rejection_date',
        'ktas.komentar',
        'ktas.created_at',
        'kota_kabupaten.nama as kota_kabupaten'
      )
        ->join('users', 'ktas.user_id', '=', 'users.id')
        ->join('kota_kabupaten', 'ktas.kabupaten_id', '=', 'kota_kabupaten.id');

      // Filter berdasarkan status (jika diberikan)
      if (!is_null($status)) {
        $query->where('ktas.status_diterima', $status);
      }

      // Urutkan berdasarkan tanggal pendaftaran terbaru
      $registrants = $query->orderBy('ktas.created_at', 'desc')->get();

      return response()->json([
        'success' => true,
        'message' => 'Registration data has been successfully retrieved.',
        'data' => $registrants,
      ], 200);
    } catch (\Exception $exception) {
      // Tangani error
      return response()->json([
        'success' => false,
        'message' => 'An error occurred while retrieving registration data.',
        'error' => $exception->getMessage(),
      ], 500);
    }
  }

  public function index()
  {
    $ktas = KTA::select(
      'ktas.id',
      'users.nama_perusahaan',
      'users.nama_direktur',
      'users.alamat_perusahaan',
      'users.email',
      'ktas.logo',
      'ktas.status_aktif',
      'ktas.tanggal_diterima',
      'ktas.expired_at',
      'kota_kabupaten.nama as kota_kabupaten'
    )
      ->join('users', 'ktas.user_id', '=', 'users.id')
      ->join('kota_kabupaten', 'ktas.kabupaten_id', '=', 'kota_kabupaten.id')
      ->where('ktas.status_diterima', 'approve')
      ->get();

    return response()->json($ktas);
  }

  // Fungsi untuk mendapatkan detail KTA berdasarkan ID
  public function show($id)
  {
    $kta = KTA::select(
      'users.nama_perusahaan',
      'users.nama_direktur',
      'users.nama_penanggung_jawab',
      'users.alamat_perusahaan',
      'users.email',
      'ktas.logo',
      'ktas.status_aktif',
      'ktas.tanggal_diterima',
      'ktas.expired_at',
      'kota_kabupaten.nama as kota_kabupaten'
    )
      ->join('users', 'ktas.user_id', '=', 'users.id')
      ->join('kota_kabupaten', 'ktas.kabupaten_id', '=', 'kota_kabupaten.id')
      ->where('ktas.id', $id)
      ->where('ktas.status_diterima', 'approve')
      ->first();

    if (!$kta) {
      return response()->json(['message' => 'KTA not found or not approved'], 404);
    }

    return response()->json($kta, 200);
  }

  // Fungsi untuk mencari KTA berdasarkan parameter yang ditentukan
  public function search(Request $request)
  {
    $searchTerm = $request->input('search'); // Ambil input pencarian

    $ktas = KTA::whereHas('user', function ($query) use ($searchTerm) {
      $query->where('nama_perusahaan', 'like', '%' . $searchTerm . '%')
        ->orWhere('alamat_perusahaan', 'like', '%' . $searchTerm . '%')
        ->orWhere('email', 'like', '%' . $searchTerm . '%');
    })->get(); // Query untuk mencari berdasarkan nama_perusahaan, alamat_perusahaan, dan email

    if ($ktas->isEmpty()) {
      return response()->json(['message' => 'KTA tidak ditemukan'], 404);
    }

    return response()->json($ktas);
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

    // Validasi file yang diunggah (misalnya hanya menerima file dengan tipe tertentu)
    $validator = Validator::make($request->all(), [
      'kta_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // Mengatur validasi file
    ]);

    if ($validator->fails()) {
      return response()->json(['message' => 'File tidak valid.', 'errors' => $validator->errors()], 422);
    }

    try {
      // Memeriksa apakah ada file yang diunggah
      if ($request->hasFile('kta_file')) {
        // Mengambil file dari request
        $file = $request->file('kta_file');

        // Membuat nama file yang unik untuk diunggah
        $fileName = 'kta_' . $id . '.' . $file->getClientOriginalExtension();

        // Menyimpan file ke dalam folder 'kta' pada storage public
        $filePath = $file->storeAs('kta', $fileName, 'public');

        // Memperbarui informasi file pada KTA
        $kta->update([
          'kta_file' => $filePath,
        ]);

        return response()->json(['message' => 'KTA uploaded successfully']);
      } else {
        return response()->json(['message' => 'No files uploaded.'], 400);
      }
    } catch (\Exception $e) {
      // Menangani error jika terjadi masalah saat proses penyimpanan
      return response()->json(['message' => 'An error occurred while uploading the file.', 'error' => $e->getMessage()], 500);
    }
  }
}
