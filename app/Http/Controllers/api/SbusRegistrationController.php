<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SBUSRegistrations;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use ZipStream\ZipStream;
use ZipArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SbusRegistrationController extends Controller
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
        'akta_asosiasi_aktif_masa_berlaku' => 'required|file|mimes:jpg,png,pdf',
        'akta_perusahaan_pendirian' => 'required|file|mimes:jpg,png,pdf',
        'akta_perubahan' => 'required|file|mimes:jpg,png,pdf',
        'pengesahan_menkumham' => 'required|file|mimes:jpg,png,pdf',
        'npwp_perusahaan' => 'required|file|mimes:jpg,png,pdf',
        'nib_berbasis_resiko' => 'required|file|mimes:jpg,png,pdf',
        'ktp_pengurus' => 'required|file|mimes:jpg,png,pdf',
        'npwp_pengurus' => 'required|file|mimes:jpg,png,pdf',
        'PJTBU' => 'required|file|mimes:jpg,png,pdf',
        'PJKSBU' => 'required|file|mimes:jpg,png,pdf',
        'foto_pas_direktur' => 'required|file|mimes:jpg,png,pdf',
        'surat_pernyataan_penanggung_jawab_mutlak' => 'required|file|mimes:jpg,png,pdf',
        'surat_pernyataan_SMAP' => 'required|file|mimes:jpg,png,pdf',
        'lampiran_TKK' => 'required|file|mimes:jpg,png,pdf',
        'neraca_keuangan_2_tahun_terakhir' => 'required|file|mimes:jpg,png,pdf',
        'akun_OSS' => 'required|file|mimes:jpg,png,pdf',
        'bukti_transfer' => 'required|file|mimes:jpg,png,pdf',
        'konstruksi_klasifikasi_id' => 'required|integer|exists:klasifikasis,id',
        'konstruksi_sub_klasifikasi_id' => 'required|integer|exists:sub_klasifikasis,id',
        'rekening_id' => 'required|integer|exists:rekening_tujuan,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $klasifikasiId = $request->konstruksi_klasifikasi_id;
    $subKlasifikasiId = $request->konstruksi_sub_klasifikasi_id;

    $folderPath = "data_user/{$userId}/SBUKonstruksi/{$subKlasifikasiId}_{$klasifikasiId}";

    $existing = SBUSRegistrations::where('user_id', $userId)
        ->where('konstruksi_sub_klasifikasi_id', $subKlasifikasiId)
        ->first();

    if ($existing && in_array($existing->status_diterima, ['pending', 'approve'])) {
        return response()->json([
            'message' => $existing->status_diterima === 'pending'
                ? 'Pendaftaranmu masih pending dan sedang diproses.'
                : 'Sub klasifikasi ini sudah disetujui dan tidak bisa didaftarkan ulang.',
        ], 403);
    }

    $fields = [
        'akta_asosiasi_aktif_masa_berlaku', 'akta_perusahaan_pendirian', 'akta_perubahan',
        'pengesahan_menkumham', 'npwp_perusahaan', 'nib_berbasis_resiko', 'ktp_pengurus',
        'npwp_pengurus', 'PJTBU', 'PJKSBU', 'foto_pas_direktur', 'surat_pernyataan_penanggung_jawab_mutlak',
        'surat_pernyataan_SMAP', 'lampiran_TKK', 'neraca_keuangan_2_tahun_terakhir',
        'akun_OSS', 'bukti_transfer'
    ];

    $uploadedPaths = [];
    foreach ($fields as $field) {
        $uploadedPaths[$field] = $request->file($field)->storeAs(
            $folderPath,
            "{$field}." . $request->file($field)->extension(),
            'local'
        );
    }

    $data = array_merge($uploadedPaths, [
        'user_id' => $userId,
        'konstruksi_klasifikasi_id' => $klasifikasiId,
        'konstruksi_sub_klasifikasi_id' => $subKlasifikasiId,
        'rekening_id' => $request->rekening_id,
        'email_perusahaan' => $user->email,
        'kop_perusahaan' => $user->logo_perusahaan ?? null,
        'no_hp_direktur' => $user->no_hp_penanggung_jawab ?? $user->nomor_penanggung_jawab,
        'status_diterima' => 'pending',
        'status_aktif' => null,
        'status_perpanjangan_sbus' => 'pending',
        'can_reapply' => 1,
    ]);

    if ($existing && $existing->status_diterima === 'rejected') {
        $existing->update($data);
        return response()->json([
            'message' => 'Pendaftaran sebelumnya yang ditolak berhasil diperbarui.',
            'data' => $existing,
        ], 200);
    }

    $alreadyExists = SBUSRegistrations::where('user_id', $userId)
        ->where('konstruksi_sub_klasifikasi_id', $subKlasifikasiId)
        ->exists();

    if ($alreadyExists) {
        return response()->json([
            'message' => 'Anda sudah mendaftarkan sub klasifikasi ini sebelumnya.',
        ], 403);
    }

    $new = SBUSRegistrations::create($data);

    return response()->json([
        'message' => 'Pendaftaran SBUS berhasil dibuat.',
        'data' => $new,
    ], 201);
  }

  public function index()
  {
    // Menampilkan daftar pendaftaran SBUS
    $registrations = SBUSRegistrations::with('user', 'KonstruksiKlasifikasi', 'KonstruksiSubKlasifikasi')->get();
    return response()->json($registrations);
  }

  public function status(Request $request, $id)
  {
      $validated = $request->validate([
          'status_diterima' => 'required|in:approve,rejected,pending',
          'komentar' => 'nullable|string|max:255',
      ]);
      $registration = SBUSRegistrations::findOrFail($id);
      // Handle status rejected
      if ($validated['status_diterima'] === 'rejected') {
          if (empty($validated['komentar'])) {
              return response()->json([
                  'message' => 'Komentar diperlukan untuk status ditolak.'
              ], 422);
          }
          // Hapus semua file terkait dari storage (jika ada)
          $fileFields = [
              'akta_asosiasi_aktif_masa_berlaku','akta_perusahaan_pendirian',
              'akta_perubahan','pengesahan_menkumham','npwp_perusahaan','nib_berbasis_resiko',
              'ktp_pengurus', 'npwp_pengurus','PJTBU','PJKSBU', 'foto_pas_direktur',
              'surat_pernyataan_penanggung_jawab_mutlak','surat_pernyataan_SMAP',
              'lampiran_TKK','neraca_keuangan_2_tahun_terakhir','akun_OSS',
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
              'expired_at' => now()->addYears(3),
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

  public function pending(Request $request)
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

        // Menggunakan eager loading dengan 'with' untuk menghindari query join manual
        $registrations = SBUSRegistrations::with([
            'user', 
            'konstruksiKlasifikasi', 
            'konstruksiSubKlasifikasi', 
            'rekening'
        ])
        ->where('status_diterima', $status)
        ->orderBy('created_at', 'desc')
        ->get();

        // Format data sesuai kebutuhan
        $data = $registrations->map(function ($registration) {
            return [
                'user_id' => $registration->user_id,
                'id' => $registration->id,
                'nama_perusahaan' => $registration->user->nama_perusahaan,
                'nama_direktur' => $registration->user->nama_direktur,
                'alamat_perusahaan' => $registration->user->alamat_perusahaan,
                'email' => $registration->user->email,
                'nomor_hp_penanggung_jawab' => $registration->nomor_hp_penanggung_jawab,
                'nama_klasifikasi' => $registration->konstruksiKlasifikasi ? $registration->konstruksiKlasifikasi->nama : null,
                'nama_sub_klasifikasi' => $registration->konstruksiSubKlasifikasi ? $registration->konstruksiSubKlasifikasi->nama : null,
                'sbu_code' => $registration->konstruksiSubKlasifikasi ? $registration->konstruksiSubKlasifikasi->sbu_code : null,
                'nama_rekening' => $registration->rekening ? $registration->rekening->nama_bank : null,
                'bukti_transfer' => $registration->bukti_transfer,
                'status_diterima' => $registration->status_diterima,
                'status_aktif' => $registration->status_aktif,
                'tanggal_diterima' => $registration->tanggal_diterima,
                'created_at' => $registration->created_at,
                'expired_at' => $registration->expired_at,
                'komentar' => $registration->komentar,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar pendaftaran SBUS berhasil diambil',
            'data' => $data,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan saat mengambil data',
            'error' => $e->getMessage(),
        ]);
    }
  }

  public function active()
  {
    $registrations = SBUSRegistrations::with(['user', 'konstruksiKlasifikasi', 'konstruksiSubKlasifikasi', 'rekening'])
        ->where('status_aktif', ['active', 'will_expired']) // ✅ Filter hanya active & will_expired
        ->latest()
        ->get();

    $formatted = $registrations->map(function ($item) {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'nama_perusahaan' => $item->user->nama_perusahaan ?? null,
            'nama_direktur' => $item->user->nama_direktur ?? null,
            'alamat_perusahaan' => $item->user->alamat_perusahaan ?? null,
            'email' => $item->user->email ?? null,
            'nomor_hp_penanggung_jawab' => $item->nomor_hp_penanggung_jawab,
            'nama_klasifikasi' => $item->konstruksiKlasifikasi->nama ?? null,
            'nama_sub_klasifikasi' => $item->konstruksiSubKlasifikasi->nama ?? null,
            'sbu_code' => $item->konstruksiSubKlasifikasi->sbu_code ?? null,
            'nama_rekening' => $item->rekening->nama_bank ?? null,
            'bukti_transfer' => $item->bukti_transfer,
            'status_diterima' => $item->status_diterima,
            'status_aktif' => $item->status_aktif,
            'tanggal_diterima' => optional($item->tanggal_diterima)->format('Y-m-d'),
            'expired_at' => optional($item->tanggal_diterima)->copy()->addYears(3)->format('Y-m-d'),
            'komentar' => $item->komentar,
            'created_at' => $item->created_at->format('Y-m-d H:i'),
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $formatted,
    ]);
  }

  public function downloadSBUSFiles($registrationId)
  {
    try {
        // Cari pendaftaran berdasarkan ID
        $registration = SBUSRegistrations::find($registrationId);

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran tidak ditemukan.',
            ], 404);
        }

        // Ambil user ID, klasifikasi dan sub-klasifikasi dari pendaftaran
        $userId = $registration->user_id;
        $klasifikasiId = $registration->konstruksi_klasifikasi_id;
        $subKlasifikasiId = $registration->konstruksi_sub_klasifikasi_id;

        // Tentukan path folder penyimpanan
        $folderPath = storage_path("app/data_user/{$userId}/SBUKonstruksi/{$subKlasifikasiId}_{$klasifikasiId}");

        if (!is_dir($folderPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Folder dokumen tidak ditemukan.',
            ], 404);
        }

        // Ambil file di folder (kecuali . dan ..)
        $files = array_diff(scandir($folderPath), ['.', '..']);

        if (count($files) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada file untuk diunduh.',
            ], 404);
        }

        // Buat path untuk file ZIP
        $zipDirectory = storage_path("app/SBU-Konstruksi");
        if (!is_dir($zipDirectory)) {
            mkdir($zipDirectory, 0777, true);
        }

        $zipPath = $zipDirectory . "/{$userId}_{$klasifikasiId}_{$subKlasifikasiId}_sbus.zip";

        // Hapus file lama jika ada
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        // Buat file ZIP baru
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat file ZIP.',
            ], 500);
        }

        foreach ($files as $file) {
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $zip->addFile($filePath, $file);
            }
        }

        $zip->close();

        // Kirim file ZIP ke user dan hapus setelah dikirim
        return response()->download($zipPath)->deleteFileAfterSend(true);

    } catch (\Exception $e) {
        \Log::error('Download SBU error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan saat mengunduh berkas.',
        ], 500);
    }
  }
}
