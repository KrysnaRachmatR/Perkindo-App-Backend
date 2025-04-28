<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SBUSRegistrations;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use ZipStream\ZipStream;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SbusRegistrationController extends Controller
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
            'klasifikasi_id' => 'required|integer|exists:klasifikasis,id',
            'sub_klasifikasi_id' => 'required|integer|exists:sub_klasifikasis,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $klasifikasiId = $request->klasifikasi_id;
        $subKlasifikasiId = $request->sub_klasifikasi_id;

        $existing = SBUSRegistrations::where('user_id', $userId)
            ->where('konstruksi_sub_klasifikasi_id', $subKlasifikasiId)
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
        $folderPath = "SBU-Konstruksi/{$userId}/{$subKlasifikasiId}_{$klasifikasiId}";
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
                'konstruksi_klasifikasi_id' => $klasifikasiId,
                'konstruksi_sub_klasifikasi_id' => $subKlasifikasiId,
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
        $alreadyOtherSub = SBUSRegistrations::where('user_id', $userId)
            ->where('konstruksi_sub_klasifikasi_id', $subKlasifikasiId)
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

        $new = SBUSRegistrations::create($data);

        return response()->json([
            'message' => 'Pendaftaran SBUS berhasil dibuat.',
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
}
