<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\SbunRegistration;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use ZipStream\ZipStream;

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
      $folderPath = "sbun/{$userId}";

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

      // Cek pendaftaran SBUN sebelumnya dengan status rejected
      $existingSbun = SbunRegistration::where('user_id', $userId)
        ->where('status_diterima', 'rejected')
        ->first();

      if ($existingSbun) {
        // Jika ada pendaftaran SBUN yang ditolak, perbarui data
        $existingSbun->update($data);
        $message = 'Pendaftaran SBUN diperbarui setelah penolakan.';
      } else {
        // Jika tidak ada, buat pendaftaran baru
        $existingSbun = SbunRegistration::create($data);
        $message = 'Pendaftaran SBUN berhasil';
      }

      return response()->json([
        'message' => $message,
        'data' => $existingSbun,
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
    try {
      // Validasi input untuk status pendaftaran
      $validated = $request->validate([
        'status_diterima' => 'required|in:approve,rejected,pending',
        'komentar' => 'nullable|string|max:255', // Komentar opsional saat status tidak ditolak
      ]);

      // Menemukan pendaftaran berdasarkan ID
      $registration = SbunRegistration::find($id);

      // Jika pendaftaran tidak ditemukan
      if (!$registration) {
        return response()->json([
          'success' => false,
          'message' => 'Pendaftaran tidak ditemukan.',
        ], 404);
      }

      // Proses jika status ditolak (rejected)
      if ($validated['status_diterima'] === 'rejected') {
        // Validasi komentar saat status ditolak
        if (empty($validated['komentar'])) {
          return response()->json(['message' => 'Komentar diperlukan untuk status ditolak.'], 422);
        }

        // Update status pendaftaran menjadi "rejected"
        $registration->status_diterima = 'rejected';
        $registration->komentar = $validated['komentar']; // Menyimpan komentar
        $registration->status_diterima = 'rejected'; // Status tidak aktif
        $registration->rejection_date = now(); // Simpan tanggal penolakan
        $registration->save();

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

        return response()->json([
          'success' => true,
          'message' => 'Pendaftaran berhasil ditolak dan dokumen dihapus.',
        ], 200);
      }

      // Proses jika status disetujui (approve)
      if ($validated['status_diterima'] === 'approve') {
        // Periksa apakah sudah disetujui sebelumnya
        if ($registration->status_diterima === 'approve') {
          return response()->json([
            'success' => false,
            'message' => 'Pendaftaran sudah disetujui sebelumnya.',
          ], 400);
        }

        // Update status pendaftaran menjadi "approve"
        $registration->status_diterima = 'approve';
        $registration->tanggal_diterima = now(); // Menyimpan tanggal diterima saat disetujui
        $registration->status_aktif = 'active'; // Menandai sebagai aktif
        $registration->expired_at = now()->addYears(2); // Masa aktif 2 tahun
        $registration->can_reapply = true; // Membolehkan pengajuan ulang jika masa berlaku habis
        $registration->komentar = null; // Tidak ada komentar saat disetujui
        $registration->rejection_date = null; // Menghapus tanggal penolakan
        $registration->save();

        return response()->json([
          'success' => true,
          'message' => 'Pendaftaran berhasil disetujui.',
          'data' => $registration->load(['user', 'nonKonstruksiKlasifikasi', 'nonKonstruksiSubKlasifikasi']),
        ], 200);
      }

      // Proses jika status pending (opsional)
      if ($validated['status_diterima'] === 'pending') {
        $registration->status_diterima = 'pending';
        $registration->save();

        return response()->json([
          'success' => true,
          'message' => 'Pendaftaran status telah diperbarui menjadi pending.',
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
        'message' => 'Terjadi kesalahan saat memperbarui status pendaftaran.',
        'error' => $exception->getMessage(),
      ], 500);
    }
  }

  public function downloadSBUNFiles($id)
  {
    try {
      // Lokasi folder file KTA berdasarkan ID KTA
      $directoryPath = "sbun/{$id}"; // Menggunakan ktaId untuk folder path

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
      $zipFileName = "sbun_files_{$id}.zip";

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


  public function index()
  {
    // Menampilkan daftar pendaftaran SBUN
    $registrations = SbunRegistration::with('user', 'nonKonstruksiKlasifikasi', 'nonKonstruksiSubKlasifikasi')->get();
    return response()->json($registrations);
  }

  public function pending(Request $request)
  {
    try {
      // Mengambil status dari query parameter, default ke 'pending' jika tidak ada
      $status = $request->query('status', 'pending', 'rejected'); // hanya satu nilai default

      // Memulai query
      $query = SbunRegistration::select(
        'sbun_registration.user_id',
        'sbun_registration.id',
        'users.nama_perusahaan',
        'users.nama_direktur',
        'users.alamat_perusahaan',
        'users.email',
        'sbun_registration.nomor_hp_penanggung_jawab',
        'sbun_registration.non_konstruksi_klasifikasi_id',
        'sbun_registration.non_konstruksi_sub_klasifikasi_id',
        'sbun_registration.rekening_id',
        'sbun_registration.bukti_transfer',
        'sbun_registration.status_diterima',
        'sbun_registration.status_aktif',
        'sbun_registration.tanggal_diterima',
        'sbun_registration.komentar'
      )
        ->join('users', 'sbun_registration.user_id', '=', 'users.id');

      // Menambahkan kondisi berdasarkan status
      // Pastikan status yang diterima adalah salah satu dari 'pending', 'rejected', 'approve'
      if (in_array($status, ['pending', 'rejected', 'approve'])) {
        $query->where('sbun_registration.status_diterima', $status);
      } else {
        // Jika status tidak valid, kembalikan error
        return response()->json([
          'success' => false,
          'message' => 'Status tidak valid. Pilih salah satu dari: pending, rejected, approve.'
        ], 422);
      }

      // Mengambil data
      $registrations = $query->orderBy('sbun_registration.created_at', 'desc')->get();

      // Menambahkan pengecekan jika tidak ada data

      return response()->json([
        'success' => true,
        'message' => 'Daftar pendaftaran SBUN berhasil diambil',
        'data' => $registrations,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil data',
        'error' => $e->getMessage(),
      ]);
    }
  }


  public function active(Request $request)
  {
    try {
      $status = $request->query('status', 'approve');

      $query = SbunRegistration::select(
        'sbun_registration.user_id',
        'sbun_registration.id',
        'users.nama_perusahaan',
        'users.nama_direktur',
        'users.alamat_perusahaan',
        'users.email',
        'sbun_registration.nomor_hp_penanggung_jawab',
        'klasifikasi.nama as nama_klasifikasi',
        'sub_klasifikasi.nama as nama_sub_klasifikasi',
        'rekening.nama_bank as nama_rekening',
        'sbun_registration.bukti_transfer',
        'sbun_registration.status_diterima',
        'sbun_registration.status_aktif',
        'sbun_registration.tanggal_diterima',
        'sbun_registration.expired_at',
        'sbun_registration.komentar'
      )
        ->join('users', 'sbun_registration.user_id', '=', 'users.id')
        ->leftJoin('non_konstruksi_klasifikasis as klasifikasi', 'sbun_registration.non_konstruksi_klasifikasi_id', '=', 'klasifikasi.id')
        ->leftJoin('non_konstruksi_sub_klasifikasis as sub_klasifikasi', 'sbun_registration.non_konstruksi_sub_klasifikasi_id', '=', 'sub_klasifikasi.id')
        ->leftJoin('rekening_tujuan as rekening', 'sbun_registration.rekening_id', '=', 'rekening.id');

      // Filter berdasarkan status
      if ($status === 'active') {
        $query->where('sbun_registration.status_aktif', 'active');
      } elseif ($status === 'approve') {
        $query->where('sbun_registration.status_diterima', 'approve');
      }

      $registrations = $query->orderBy('sbun_registration.created_at', 'desc')->get();

      return response()->json([
        'success' => true,
        'message' => 'Daftar pendaftaran SBUN berhasil diambil',
        'data' => $registrations,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil data',
        'error' => $e->getMessage(),
      ]);
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
      $registrations = SbunRegistration::where('status_aktif', 'active')
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

  public function getSbun(Request $request)
  {
    // Middleware auth akan memastikan token valid
    $user = auth()->user();

    // Pastikan user sudah login
    if (!$user) {
      return response()->json(['message' => 'User not authenticated'], 401);
    }

    // Cari data SBUS berdasarkan user ID
    $sbun = SbunRegistration::where('user_id', $user->id)->first();


    // Kembalikan data SBUS
    return response()->json([
      'status_diterima' => $sbun->status_diterima,
      'komentar' => $sbun->komentar,
      'sbun' => $sbun
    ], 200);
  }
}
