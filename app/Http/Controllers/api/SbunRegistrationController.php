<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\SbunRegistration;
use App\Models\NonKonstruksiKlasifikasi;
use App\Models\NonKonstruksiSubKlasifikasi;
use App\Models\User;
use App\Models\Admin;
use ZipStream\ZipStream;
use ZipArchive;
use Carbon\Carbon;

class SbunRegistrationController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();
        $userId = $user->id;
    
        $kta = $user->kta;
    
        if (!$kta || $kta->status_aktif !== 'active') {
            return response()->json([
                'message' => 'Anda belum memiliki KTA aktif atau masih dalam proses.',
            ], 403);
        }

        $tanggalExpired = Carbon::parse($kta->tanggal_diterima)->addYear();
        if (Carbon::now()->gt($tanggalExpired)) {
            return response()->json([
                'message' => 'KTA Anda sudah tidak aktif. Silakan perpanjang terlebih dahulu.',
            ], 403);
        }
    
        $validator = Validator::make($request->all(), [
            'akta_pendirian' => 'required|file|mimes:jpg,png,pdf',
            'npwp_perusahaan' => 'required|file|mimes:jpg,png,pdf',
            'nib' => 'required|file|mimes:jpg,png,pdf',
            'ktp_penanggung_jawab' => 'required|file|mimes:jpg,png,pdf',
            'foto_penanggung_jawab' => 'required|file|mimes:jpg,png,pdf',
            'npwp_penanggung_jawab' => 'required|file|mimes:jpg,png,pdf',
            'ktp_pemegang_saham' => 'required|file|mimes:jpg,png,pdf',
            'npwp_pemegang_saham' => 'required|file|mimes:jpg,png,pdf',
            'non_konstruksi_klasifikasi_id' => 'required|integer|exists:non_konstruksi_klasifikasis,id',
            'non_konstruksi_sub_klasifikasi_id' => 'required|integer|exists:non_konstruksi_sub_klasifikasis,id',
            'bukti_transfer' => 'required|file|mimes:jpg,png,pdf',
            'rekening_id' => 'required|integer|exists:rekening_tujuan,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $klasifikasiId = $request->non_konstruksi_klasifikasi_id;
        $subKlasifikasiId = $request->non_konstruksi_sub_klasifikasi_id;
    
        $folderPath = "data_user/{$userId}/SBUNonKonstruksi/{$subKlasifikasiId}_{$klasifikasiId}";
    
        $existing = SbunRegistration::where('user_id', $userId)
            ->where('non_konstruksi_sub_klasifikasi_id', $subKlasifikasiId)
            ->first();
    
        if ($existing && in_array($existing->status_diterima, ['pending', 'approved'])) {
            return response()->json([
                'message' => $existing->status_diterima === 'pending'
                    ? 'Pendaftaranmu masih pending dan sedang diproses.'
                    : 'Sub klasifikasi ini sudah disetujui dan tidak bisa didaftarkan ulang.',
            ], 403);
        }
    
        // Simpan file ke path dinamis dengan nama tetap (tidak perlu timestamp)
        $aktaPendirianPath = $request->file('akta_pendirian')->storeAs(
            $folderPath,
            'akta_pendirian.' . $request->file('akta_pendirian')->extension(),
            'local'
        );
    
        $npwpPerusahaanPath = $request->file('npwp_perusahaan')->storeAs(
            $folderPath,
            'npwp_perusahaan.' . $request->file('npwp_perusahaan')->extension(),
            'local'
        );
    
        $buktiTransferPath = $request->file('bukti_transfer')->storeAs(
            $folderPath,
            'bukti_transfer.' . $request->file('bukti_transfer')->extension(),
            'local'
        );
    
        $data = [
            'user_id' => $userId,
            'non_konstruksi_klasifikasi_id' => $klasifikasiId,
            'non_konstruksi_sub_klasifikasi_id' => $subKlasifikasiId,
            'akta_pendirian' => $aktaPendirianPath,
            'npwp_perusahaan' => $npwpPerusahaanPath,
            'bukti_transfer' => $buktiTransferPath,
            'rekening_id' => $request->rekening_id,
            'nomor_hp_penanggung_jawab' => $user->no_hp_penanggung_jawab ?? $user->nomor_penanggung_jawab,
            'email_perusahaan' => $user->email,
            'logo_perusahaan' => $user->logo_perusahaan,
            'status_diterima' => 'pending',
            'status_aktif' => null,
        ];
    
        if ($existing && $existing->status_diterima === 'rejected') {
            $existing->update($data);
            return response()->json([
                'message' => 'Pendaftaran yang sebelumnya ditolak berhasil diperbarui.',
                'data' => $existing,
            ], 200);
        }
    
        $alreadyExists = SbunRegistration::where('user_id', $userId)
            ->where('non_konstruksi_sub_klasifikasi_id', $subKlasifikasiId)
            ->exists();
    
        if ($alreadyExists) {
            return response()->json([
                'message' => 'Anda sudah mendaftarkan sub klasifikasi ini sebelumnya.',
            ], 403);
        }
    
        $new = SbunRegistration::create($data);
    
        return response()->json([
            'message' => 'Pendaftaran SBUN berhasil dibuat.',
            'data' => $new,
        ], 201);
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
            $folderPath = storage_path("app/data_user/{$userId}/SBUNonKonstruksi/{$subKlasifikasiId}_{$klasifikasiId}");

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
}?>