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
        $message = 'Pengajuan KTA diperbarui setelah penolakan.';
      } else {
        // Jika tidak ada pendaftaran yang ditolak sebelumnya, buat data KTA baru
        $existingKTA = KTA::create($data);
        $message = 'Pengajuan KTA berhasil disubmit';
      }

      return response()->json(['message' => $message, 'kta' => $existingKTA], 201);
    } catch (\Exception $e) {
      return response()->json(['message' => 'Terjadi kesalahan pada server', 'error' => $e->getMessage()], 500);
    }
  }



  // Fungsi untuk memperpanjang KTA
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

    return response()->json(['message' => 'Perpanjangan KTA berhasil diajukan.'], 200);
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


  // Fungsi untuk menyetujui atau menolak perpanjangan KTA
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
    } else if ($request->status_perpanjangan_kta === 'rejected') {
      $kta->status_perpanjangan_kta = 'rejected';
      $kta->komentar = $request->komentar; // Simpan komentar penolakan dari admin
    }

    $kta->save();

    return response()->json([
      'message' => 'Status perpanjangan KTA berhasil diperbarui.',
      'kta' => $kta
    ]);
  }

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
          'message' => 'Pendaftaran KTA tidak ditemukan.',
        ], 404);
      }

      // Proses jika status adalah "approve"
      if ($validated['status_diterima'] === 'approve') {
        // Periksa apakah sudah disetujui sebelumnya
        if ($ktaRegistration->status_diterima === 'approve') {
          return response()->json([
            'success' => false,
            'message' => 'Pendaftaran KTA sudah disetujui sebelumnya.',
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
          'message' => 'Pendaftaran KTA berhasil disetujui.',
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
            'message' => 'Pendaftaran KTA yang sudah disetujui dan aktif tidak bisa ditolak.',
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
          'message' => 'Pendaftaran KTA berhasil ditolak. Pengguna dapat langsung mendaftar ulang. Dokumen dihapus.',
        ], 200);
      }
    } catch (\Illuminate\Validation\ValidationException $validationException) {
      // Tangani kesalahan validasi
      return response()->json([
        'success' => false,
        'message' => 'Validasi gagal.',
        'errors' => $validationException->errors(),
      ], 422);
    } catch (\Exception $exception) {
      // Tangani kesalahan umum
      return response()->json([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memperbarui status pendaftaran KTA.',
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
        'message' => 'Data pendaftaran berhasil diambil.',
        'data' => $registrants,
      ], 200);
    } catch (\Exception $exception) {
      // Tangani error
      return response()->json([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil data pendaftaran.',
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
          'message' => 'Berkas untuk KTA ini tidak ditemukan.',
        ], 404);
      }

      // Ambil semua file dalam folder KTA
      $files = Storage::disk('local')->files($directoryPath);

      // Jika folder tidak mengandung file
      if (empty($files)) {
        return response()->json([
          'success' => false,
          'message' => 'Folder tidak mengandung berkas.',
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
        'message' => 'Berkas untuk KTA ini tidak ditemukan.',
      ], 404);
    }
  }
  // Fungsi untuk mendapatkan data KTA berdasarkan ID
 public function getKTA(Request $request)
 {
     // Middleware auth akan memastikan token valid
     $user = auth()->user();
 
     // Pastikan user sudah login
     if (!$user) {
         return response()->json(['message' => 'User not authenticated'], 401);
     }
 
     // Cari data KTA berdasarkan user ID
     $kta = KTA::with('user')->where('user_id', $user->id)->first();
 
     // Jika data KTA tidak ditemukan
     if (!$kta) {
         return response()->json(['hasKTA' => false, 'message' => 'No KTA found for this user'], 404);
     }
 
     // Cek apakah status_diterima adalah approve
     $hasKTA = $kta->status_diterima === 'approve';
 
     // Kembalikan data KTA beserta hasKTA
     return response()->json([
         'hasKTA' => $hasKTA,
         'status_diterima' => $kta->status_diterima,
         'kta' => $kta
     ], 200);
 }
}
