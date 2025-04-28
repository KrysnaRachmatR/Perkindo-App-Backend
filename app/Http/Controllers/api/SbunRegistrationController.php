<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\SbunRegistration;
use App\Models\NonKonstruksiKlasifikasi;
use App\Models\NonKonstruksiSubKlasifikasi;
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
  
  public function downloadSBUNFiles($registrationId)
{
    try {
        // Cari pendaftaran berdasarkan ID pendaftaran
        $registration = SbunRegistration::find($registrationId);

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran tidak ditemukan.',
            ], 404);
        }

        // Ambil data user_id, klasifikasi dan sub-klasifikasi
        $userId = $registration->user_id;
        $subKlasifikasiId = $registration->non_konstruksi_sub_klasifikasi_id;
        $klasifikasiId = $registration->non_konstruksi_klasifikasi_id;

        // Path folder
        $folderPath = storage_path("app/SBU-Non Konstruksi/{$userId}/{$subKlasifikasiId}_{$klasifikasiId}");

        if (!is_dir($folderPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Folder dokumen untuk klasifikasi dan sub-klasifikasi ini tidak ditemukan.',
            ], 404);
        }

        // Pastikan folder menyimpan file, bukan kosong
        $files = array_diff(scandir($folderPath), ['.', '..']);
        if (empty($files)) {
            return response()->json([
                'success' => false,
                'message' => 'Folder ditemukan, tetapi tidak ada file untuk diunduh.',
            ], 404);
        }

        // Nama file ZIP
        $zipPath = storage_path("app/SBU-Non Konstruksi/{$userId}_{$klasifikasiId}_{$subKlasifikasiId}_sbun.zip");

        // Hapus file ZIP lama kalau ada
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        // Buat file ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat file ZIP.',
            ], 500);
        }

        // Tambahkan file ke dalam ZIP
        foreach ($files as $file) {
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $zip->addFile($filePath, $file); // nama dalam zip tetap sama
            }
        }

        $zip->close();

        // Kirim file ke user
        return response()->download($zipPath)->deleteFileAfterSend(true);

    } catch (\Exception $e) {
        \Log::error('Download error: ' . $e->getMessage());
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
          $status = $request->query('status', 'pending');
  
          // Validasi nilai status
          if (!in_array($status, ['pending', 'rejected', 'approve'])) {
              return response()->json([
                  'success' => false,
                  'message' => 'Status tidak valid. Pilih salah satu dari: pending, rejected, approve.'
              ], 422);
          }
  
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
                  'sbun.created_at',
                  DB::raw("DATE_ADD(sbun.tanggal_diterima, INTERVAL 3 YEAR) as expired_at"),
                  'sbun.komentar'
              )
              ->join('users', 'sbun.user_id', '=', 'users.id')
              ->leftJoin('non_konstruksi_klasifikasis as klasifikasi', 'sbun.non_konstruksi_klasifikasi_id', '=', 'klasifikasi.id')
              ->leftJoin('non_konstruksi_sub_klasifikasis as sub_klasifikasi', 'sbun.non_konstruksi_sub_klasifikasi_id', '=', 'sub_klasifikasi.id')
              ->leftJoin('rekening_tujuan as rekening', 'sbun.rekening_id', '=', 'rekening.id')
              ->where('sbun.status_diterima', $status)
              ->orderBy('sbun.created_at', 'desc');
  
          $registrations = $query->get();
  
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
                'rekening.nomor_rekening as nomor_rekening',
                'sbun.bukti_transfer',
                'sbun.status_diterima',
                'sbun.status_aktif',
                'sbun.tanggal_diterima',
                'sbun.expired_at',
                'sbun.komentar'
            )
            ->join('users', 'sbun.user_id', '=', 'users.id')
            ->leftJoin('non_konstruksi_klasifikasis as klasifikasi', 'sbun.non_konstruksi_klasifikasi_id', '=', 'klasifikasi.id')
            ->leftJoin('non_konstruksi_sub_klasifikasis as sub_klasifikasi', 'sbun.non_konstruksi_sub_klasifikasi_id', '=', 'sub_klasifikasi.id')
            ->leftJoin('rekening_tujuan as rekening', 'sbun.rekening_id', '=', 'rekening.id')
            ->whereIn('sbun.status_aktif', ['active', 'will_expire'])

            ->whereDate('sbun.expired_at', '>', now()) // hanya yang belum expired
            ->orderBy('sbun.created_at', 'desc');

        $registrations = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pendaftaran SBUN yang aktif berhasil diambil',
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

        if (!$searchTerm) {
            return response()->json(['message' => 'Parameter pencarian tidak diberikan.'], 400);
        }

        $registrations = SbunRegistration::where('status_aktif', 'active')
            ->where(function ($query) use ($searchTerm) {
                $query->orWhere('non_konstruksi_klasifikasi_id', 'like', '%' . $searchTerm . '%')
                    ->orWhere('non_konstruksi_sub_klasifikasi_id', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email_perusahaan', 'like', '%' . $searchTerm . '%')
                    ->orWhere('nomor_hp_penanggung_jawab', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('nama_perusahaan', 'like', '%' . $searchTerm . '%')
                            ->orWhere('email', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('nonKonstruksiSubKlasifikasi', function ($subQuery) use ($searchTerm) {
                        $subQuery->where('sbu_code', 'like', '%' . $searchTerm . '%');
                    });
            })
            ->with(['user', 'nonKonstruksiSubKlasifikasi']) // tambahkan relasi ini agar hasil lengkap
            ->get();

        if ($registrations->isEmpty()) {
            return response()->json(['message' => 'SBU tidak ditemukan.'], 404);
        }

        return response()->json([
            'message' => 'Data ditemukan.',
            'data' => $registrations
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
