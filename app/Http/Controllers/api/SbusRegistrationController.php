<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SBUSRegistrations;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use ZipStream\ZipStream;

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

      // Cek pendaftaran SBUN sebelumnya dengan status rejected
      $existingSbu = SBUSRegistrations::where('user_id', $userId)
        ->where('status_diterima', 'rejected')
        ->first();

      if ($existingSbu) {
        // Jika ada pendaftaran SBUS yang ditolak, perbarui data
        $existingSbu->update($data);
        $message = 'Pendaftaran SBUS diperbarui setelah penolakan.';
      } else {
        // Jika tidak ada, buat pendaftaran baru
        $existingSbu = SBUSRegistrations::create($data);
        $message = 'Pendaftaran SBUS berhasil';
      }

      return response()->json([
        'message' => $message,
        'data' => $existingSbu,
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
      'komentar' => 'required_if:status_diterima,rejected|string|max:255', // Komentar diperlukan hanya jika status ditolak
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

      // Update status dan komentar
      $registration->status_diterima = 'rejected';
      $registration->komentar = $request->komentar; // Menyimpan komentar admin
      $registration->rejection_date = now(); // Tanggal penolakan
      $registration->status_diterima = 'rejected'; // Set status_aktif menjadi inactive
      $registration->save();

      // Daftar file yang harus dihapus jika status ditolak
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
    }

    // Proses jika status disetujui (approve)
    if ($request->status_diterima === 'approve') {
      // Pastikan tidak ada status approve sebelumnya
      if ($registration->status_diterima === 'approve') {
        return response()->json(['message' => 'Pendaftaran sudah disetujui sebelumnya.'], 400);
      }

      // Update status pendaftaran menjadi "approve"
      $registration->status_diterima = 'approve';
      $registration->komentar = $request->komentar ?? null; // Menyimpan komentar jika ada
      $registration->status_aktif = 'active';
      $registration->tanggal_diterima = now(); // Menyimpan tanggal diterima saat disetujui
      $registration->expired_at = now()->addYears(2); // Masa aktif 2 tahun
      $registration->can_reapply = true; // Membolehkan pengajuan ulang setelah masa berlaku habis
      $registration->rejection_date = null; // Menghapus tanggal penolakan jika disetujui
      $registration->save();

      return response()->json([
        'message' => 'Pendaftaran berhasil disetujui.',
        'registration' => $registration->load(['user', 'KonstruksiKlasifikasi', 'KonstruksiSubKlasifikasi']),
      ], 200);
    }

    // Proses jika status pending (opsional)
    if ($request->status_diterima === 'pending') {
      $registration->status_diterima = 'pending';
      $registration->save();

      return response()->json([
        'message' => 'Pendaftaran status telah diperbarui menjadi pending.',
      ], 200);
    }
  }

  public function pending(Request $request)
  {
    try {
      // Mengambil status dari query parameter, default ke 'pending' jika tidak ada
      $status = $request->query('status', 'pending'); // hanya satu nilai default

      // Memulai query
      $query = SBUSRegistrations::select(
        'sbus_registration.user_id',
        'sbus_registration.id',
        'users.nama_perusahaan',
        'users.nama_direktur',
        'users.alamat_perusahaan',
        'users.email',
        'sbus_registration.nomor_hp_penanggung_jawab',
        'sbus_registration.konstruksi_klasifikasi_id',
        'sbus_registration.konstruksi_sub_klasifikasi_id',
        'sbus_registration.rekening_id',
        'sbus_registration.bukti_transfer',
        'sbus_registration.status_diterima',
        'sbus_registration.status_aktif',
        'sbus_registration.tanggal_diterima',
        'sbus_registration.komentar'
      )
        ->join('users', 'sbus_registration.user_id', '=', 'users.id');

      // Menambahkan kondisi berdasarkan status
      if (in_array($status, ['pending', 'rejected', 'approve'])) {
        $query->where('sbus_registration.status_diterima', $status);
      } else {
        // Jika status tidak valid, kembalikan error
        return response()->json([
          'success' => false,
          'message' => 'Status tidak valid. Pilih salah satu dari: pending, rejected, approve.'
        ], 422);
      }

      // Mengambil data
      $registrations = $query->orderBy('sbus_registration.created_at', 'desc')->get();

      return response()->json([
        'success' => true,
        'message' => 'Daftar pendaftaran SBUS berhasil diambil',
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

      $query = SBUSRegistrations::select(
        'sbus_registration.user_id',
        'sbus_registration.id',
        'users.nama_perusahaan',
        'users.nama_direktur',
        'users.alamat_perusahaan',
        'users.email',
        'sbus_registration.nomor_hp_penanggung_jawab',
        'klasifikasis.nama as nama_klasifikasi',
        'sub_klasifikasis.nama as nama_sub_klasifikasi',
        'rekening_tujuan.nama_bank as nama_rekening',
        'sbus_registration.bukti_transfer',
        'sbus_registration.status_diterima',
        'sbus_registration.status_aktif',
        'sbus_registration.tanggal_diterima',
        'sbus_registration.expired_at',
        'sbus_registration.komentar'
      )
        ->join('users', 'sbus_registration.user_id', '=', 'users.id')
        ->leftJoin('klasifikasis', 'sbus_registration.konstruksi_klasifikasi_id', '=', 'klasifikasis.id')
        ->leftJoin('sub_klasifikasis', 'sbus_registration.konstruksi_sub_klasifikasi_id', '=', 'sub_klasifikasis.id')
        ->leftJoin('rekening_tujuan', 'sbus_registration.rekening_id', '=', 'rekening_tujuan.id');

      // Filter berdasarkan status
      if ($status === 'active') {
        $query->where('sbus_registration.status_aktif', 'active');
      } elseif ($status === 'approve') {
        $query->where('sbus_registration.status_diterima', 'approve');
      }

      $registrations = $query->orderBy('sbus_registration.created_at', 'desc')->get();

      return response()->json([
        'success' => true,
        'message' => 'Daftar pendaftaran SBUS berhasil diambil',
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


  public function downloadSBUSFiles($id)
  {
    try {
      // Lokasi folder file SBU berdasarkan ID SBU
      $directoryPath = "sbus/{$id}"; // Menggunakan sbusId untuk folder path

      // Verifikasi apakah folder SBU ada di storage lokal
      if (!Storage::disk('local')->exists($directoryPath)) {
        return response()->json([
          'success' => false,
          'message' => 'Berkas untuk SBU ini tidak ditemukan.',
        ], 404);
      }

      // Ambil semua file dalam folder SBU
      $files = Storage::disk('local')->files($directoryPath);

      // Jika folder tidak mengandung file
      if (empty($files)) {
        return response()->json([
          'success' => false,
          'message' => 'Folder tidak mengandung berkas.',
        ], 404);
      }

      // Nama file ZIP
      $zipFileName = "sbus_files_{$id}.zip";

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
      Log::error('Error downloading SBU files for SBU ' . $id . ': ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Berkas untuk SBU ini tidak ditemukan.',
      ], 404);
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

  public function getSbus(Request $request)
  {
    // Middleware auth akan memastikan token valid
    $user = auth()->user();

    // Pastikan user sudah login
    if (!$user) {
      return response()->json(['message' => 'User not authenticated'], 401);
    }

    // Cari data SBUS berdasarkan user ID
    $sbus = SBUSRegistrations::where('user_id', $user->id)->first();

    // Kembalikan data SBUS
    return response()->json([
      'status_diterima' => $sbus->status_diterima,
      'komentar' => $sbus->komentar,
      'sbus' => $sbus
    ], 200);
  }
}
