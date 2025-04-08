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
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SbunRegistrationController extends Controller
{
    public function store(Request $request)
{
    try {
        $userId = auth()->id();
        $user = auth()->user(); // Ambil user aktif

        // Ambil relasi KTA user
        $kta = $user->kta;

        // Cek apakah user punya KTA dan statusnya approved
        if (!$kta || $kta->status_aktif !== 'active') {
            return response()->json([
                'message' => 'Anda belum memiliki KTA aktif atau masih dalam proses.',
            ], 403);
        }

        // Cek masa aktif KTA (1 tahun dari tanggal_diterima)
        $tanggalDiterima = $kta->tanggal_diterima;
        $tanggalExpired = Carbon::parse($tanggalDiterima)->addYear();

        if (Carbon::now()->gt($tanggalExpired)) {
            return response()->json([
                'message' => 'KTA Anda sudah tidak aktif. Silakan perpanjang terlebih dahulu.',
            ], 403);
        }

        // Validasi awal
        $validator = Validator::make($request->all(), [
            'non_konstruksi_klasifikasi_id' => 'required|integer|exists:non_konstruksi_klasifikasis,id',
            'non_konstruksi_sub_klasifikasi_id' => 'required|integer|exists:non_konstruksi_sub_klasifikasis,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $klasifikasiId = $request->non_konstruksi_klasifikasi_id;
        $subKlasifikasiId = $request->non_konstruksi_sub_klasifikasi_id;

        $existing = SbunRegistration::where('user_id', $userId)
            ->where('non_konstruksi_sub_klasifikasi_id', $subKlasifikasiId)
            ->first();

        if ($existing) {
            if (in_array($existing->status_diterima, ['pending', 'approved'])) {
                return response()->json([
                    'message' => $existing->status_diterima === 'pending'
                        ? 'Pendaftaranmu masih pending dan sedang diproses.'
                        : 'Sub klasifikasi ini sudah disetujui dan tidak bisa didaftarkan ulang.',
                ], 403);
            }
        }

        // Daftar field-file
        $fileFields = [
            'akta_pendirian',
            'npwp_perusahaan',
            'ktp_penanggung_jawab',
            'ktp_pemegang_saham',
            'npwp_pemegang_saham',
            'logo_perusahaan',
            'bukti_transfer',
        ];

        $optionalFields = [
            'nomor_hp_penanggung_jawab' => 'numeric',
            'email_perusahaan' => 'email',
            'rekening_id' => 'integer|exists:rekening_tujuan,id',
        ];

        // Validasi dinamis file & field opsional
        $dynamicRules = [];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $dynamicRules[$field] = 'file|mimes:jpg,png,pdf';
            }
        }

        foreach ($optionalFields as $field => $rule) {
            if ($request->filled($field)) {
                $dynamicRules[$field] = $rule;
            }
        }

        if (!empty($dynamicRules)) {
            $validator = Validator::make($request->all(), $dynamicRules);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
        }

        // Simpan file yang diupload
        $folderPath = "SBU-Non Konstruksi/{$userId}/{$subKlasifikasiId}_{$klasifikasiId}";
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

        // Gabungkan data dari request dan file
        $data = array_merge(
            $request->only(array_keys($optionalFields)),
            $uploadedFiles,
            [
                'non_konstruksi_klasifikasi_id' => $klasifikasiId,
                'non_konstruksi_sub_klasifikasi_id' => $subKlasifikasiId,
                'user_id' => $userId,
            ]
        );

        // Jika sebelumnya ditolak, update
        if ($existing && $existing->status_diterima === 'rejected') {
            $existing->update($data);
            return response()->json([
                'message' => 'Pendaftaran yang sebelumnya ditolak berhasil diperbarui.',
                'data' => $existing,
            ], 200);
        }

        // Cegah duplikat sub klasifikasi
        $alreadyOtherSub = SbunRegistration::where('user_id', $userId)
            ->where('non_konstruksi_sub_klasifikasi_id', $subKlasifikasiId)
            ->exists();

        if ($alreadyOtherSub) {
            return response()->json([
                'message' => 'Anda sudah mendaftarkan sub klasifikasi ini sebelumnya.',
            ], 403);
        }

        // Validasi field wajib untuk pendaftaran baru
        $requiredFields = array_merge($fileFields, ['nomor_hp_penanggung_jawab', 'email_perusahaan', 'rekening_id']);

        foreach ($requiredFields as $field) {
            if (
                (!$request->filled($field) && !$request->hasFile($field)) &&
                !isset($uploadedFiles[$field])
            ) {
                return response()->json([
                    'message' => "Kolom $field wajib diisi untuk pendaftaran baru.",
                ], 422);
            }
        }

        // Tambah data baru
        $data['status_diterima'] = 'pending';
        $data['status_aktif'] = null;

        $new = SbunRegistration::create($data);

        return response()->json([
            'message' => 'Pendaftaran SBUN berhasil dibuat.',
            'data' => $new,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan.',
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
    $validated = $request->validate([
        'status_diterima' => 'required|in:approve,rejected,pending',
        'komentar' => 'nullable|string|max:255',
    ]);
    $registration = SbunRegistration::findOrFail($id);
      // Handle status rejected
      if ($validated['status_diterima'] === 'rejected') {
        if (empty($validated['komentar'])) {
            return response()->json([
                'message' => 'Komentar diperlukan untuk status ditolak.'
            ], 422);
        }
          // Hapus semua file terkait dari storage (jika ada)
          $fileFields = [
            'akta_pendirian', 'npwp_perusahaan', 'ktp_penanggung_jawab',
            'ktp_pemegang_saham', 'npwp_pemegang_saham', 'logo_perusahaan',
            'bukti_transfer'
          ];
  
          foreach ($fileFields as $field) {
              if ($registration->$field && Storage::disk('local')->exists($registration->$field)) {
                  Storage::disk('local')->delete($registration->$field);
              }
          }
  
          // Hapus record dari database
          $registration->delete();
  
          return response()->json([
              'success' => true,
              'message' => 'Pendaftaran ditolak dan semua data serta dokumen telah dihapus permanen.',
          ], 200);
      }
  
      // Handle status approve
      if ($validated['status_diterima'] === 'approve') {
          if ($registration->status_diterima === 'approve') {
              return response()->json([
                  'success' => false,
                  'message' => 'Pendaftaran sudah disetujui sebelumnya.',
              ], 400);
          }
  
          $registration->update([
              'status_diterima' => 'approve',
              'tanggal_diterima' => now(),
              'status_aktif' => 'active',
              'expired_at' => now()->addYears(1),
              'komentar' => null,
              'rejection_date' => null,
          ]);
  
          return response()->json([
              'success' => true,
              'message' => 'Pendaftaran berhasil disetujui.',
              'data' => $registration,
          ], 200);
      }
  
      // Handle status pending
      $registration->update([
          'status_diterima' => 'pending',
          'komentar' => null,
          'rejection_date' => null,
      ]);
  
      return response()->json([
          'success' => true,
          'message' => 'Pendaftaran berhasil diubah menjadi pending.',
      ], 200);
  }
  

  public function downloadSBUNFiles($id)
  {
      try {
          // Cari pendaftaran berdasarkan ID sbun_registration
          $registration = SbunRegistration::find($id);

          if (!$registration) {
              return response()->json([
                  'success' => false,
                  'message' => 'Pendaftaran tidak ditemukan.',
              ], 404);
          }

          // Ambil userId, subKlasifikasiId, dan klasifikasiId dari database
          $userId = $registration->user_id;
          $subKlasifikasiId = $registration->sub_klasifikasi_id;
          $klasifikasiId = $registration->klasifikasi_id;

          // Format direktori penyimpanan file
          $directoryPath = "SBU-Non Konstruksi/{$userId}/{$subKlasifikasiId}_{$klasifikasiId}";

          // Periksa apakah folder ada di penyimpanan lokal
          if (!Storage::disk('local')->exists($directoryPath)) {
              return response()->json([
                  'success' => false,
                  'message' => 'ROKOKAN SEK LE, KETOK LEK MUMET LE, KODINGANMU UELEK LE',
              ], 404);
          }

          // Ambil semua file dalam folder
          $files = Storage::disk('local')->files($directoryPath);

          if (empty($files)) {
              return response()->json([
                  'success' => false,
                  'message' => 'Folder tidak mengandung berkas.',
              ], 404);
          }

          // Nama file ZIP
          $zipFileName = "sbun_files_{$id}.zip";

          // Membuat ZIP dan mengirimkannya ke browser
          return response()->stream(function () use ($files) {
              try {
                  $zip = new ZipStream();

                  foreach ($files as $file) {
                      $filePath = storage_path("app/{$file}");

                      if (!file_exists($filePath)) {
                          Log::warning("File tidak ditemukan: {$filePath}");
                          continue;
                      }

                      $zip->addFileFromPath(basename($file), $filePath);
                  }

                  $zip->finish();
              } catch (\Exception $e) {
                  Log::error('Error creating ZIP file: ' . $e->getMessage());
                  throw $e;
              }
          }, 200, [
              'Content-Type' => 'application/zip',
              'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
          ]);

      } catch (\Exception $e) {
          Log::error('Error downloading SBUN files for ID ' . $id . ': ' . $e->getMessage());
          return response()->json([
              'success' => false,
              'message' => 'Terjadi kesalahan saat mengunduh berkas.',
          ], 500);
      }
  }

  public function index()
  {
    // Menampilkan daftar pendaftaran SBUN
    $registrations = SbunRegistration::with('user', 'nonKonstruksiKlasifikasi', 'nonKonstruksiSubKlasifikasi')->get();
    return response()->json($registrations);
  }

  public function allPending(Request $request)
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

        $query = DB::table('sbun_registration as sbun')
            ->select(
                'sbun.user_id',
                'sbun.id',
                'users.nama_perusahaan',
                'users.nama_direktur',
                'users.alamat_perusahaan',
                'users.email',
                'sbun.nomor_hp_penanggung_jawab',
                'klasifikasi.nama as nama_klasifikasi',
                'sub_klasifikasi.nama as nama_sub_klasifikasi',
                'sub_klasifikasi.sbu_code',
                'rekening.nama_bank as nama_rekening',
                'sbun.bukti_transfer',
                'sbun.status_diterima',
                'sbun.status_aktif',
                'sbun.tanggal_diterima',
                DB::raw("DATE_ADD(sbun.tanggal_diterima, INTERVAL 3 YEAR) as expired_at"),
                'sbun.komentar'
            )
            ->join('users', 'sbun.user_id', '=', 'users.id')
            ->leftJoin('non_konstruksi_klasifikasis as klasifikasi', 'sbun.non_konstruksi_klasifikasi_id', '=', 'klasifikasi.id')
            ->leftJoin('non_konstruksi_sub_klasifikasis as sub_klasifikasi', 'sbun.non_konstruksi_sub_klasifikasi_id', '=', 'sub_klasifikasi.id')
            ->leftJoin('rekening_tujuan as rekening', 'sbun.rekening_id', '=', 'rekening.id');

        // Filter berdasarkan status
        if ($status === 'active') {
            $query->where('sbun.status_aktif', 'active');
        } elseif ($status === 'approve') {
            $query->where('sbun.status_diterima', 'approve');
        }

        $registrations = $query->orderBy('sbun.created_at', 'desc')->get();

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
}
