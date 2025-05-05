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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use ZipArchive;
use PhpOffice\PhpWord\PhpWord;
use Illuminate\Support\Facades\File;


class KtaController extends Controller
{
  // ----------USER CONTROLLER----------\\
  public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'akta_pendirian' => 'nullable|file|mimes:pdf,jpg,png',
        'npwp_perusahaan' => 'nullable|file|mimes:pdf,jpg,png',
        'nib' => 'nullable|file|mimes:pdf,jpg,png',
        'kabupaten_id' => 'required|integer|exists:kota_kabupaten,id',
        'rekening_id' => 'required|integer|exists:rekening_tujuan,id',
        'bukti_transfer' => 'nullable|file|mimes:pdf,jpg,png',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
    }

    return DB::transaction(function () use ($request) {
        $user = Auth::user();
        $userId = $user->id;
        $basePath = "data_user/{$userId}/KTA";

        $fileFields = ['akta_pendirian', 'npwp_perusahaan', 'nib', 'bukti_transfer'];

        $existingKTA = KTA::where('user_id', $userId)->first();

        $email = $user->email;
        $pjbu = [
            'ktp' => $user->ktp_penanggung_jawab,
            'npwp' => $user->npwp_penanggung_jawab,
        ];
        $data_pengurus = [
            'ktp' => $user->ktp_pemegang_saham,
            'npwp' => $user->npwp_pemegang_saham,
        ];
        $logo = $user->logo_perusahaan;

        if ($existingKTA) {
            if ($existingKTA->status_diterima === 'pending') {
                return response()->json([
                    'message' => 'Anda sudah mengajukan KTA. Mohon tunggu persetujuan dari admin.'
                ], 403);
            }

            if ($existingKTA->status_diterima === 'approved' && $existingKTA->status_aktif === 'active') {
                return response()->json([
                    'message' => 'Anda sudah memiliki KTA yang aktif hingga ' . Carbon::parse($existingKTA->expired_at)->format('d-m-Y') . '. Tidak bisa mengajukan KTA baru.'
                ], 403);
            }

            if ($existingKTA->status_diterima === 'rejected') {
                foreach ($fileFields as $field) {
                    if ($request->hasFile($field)) {
                        if ($existingKTA->$field) {
                            Storage::delete($existingKTA->$field);
                        }
                        $filename = time() . '_' . $request->file($field)->getClientOriginalName();
                        $existingKTA->$field = $request->file($field)->storeAs($basePath, $filename);

                    }
                }

                $existingKTA->update([
                    'kabupaten_id' => $request->kabupaten_id,
                    'rekening_id' => $request->rekening_id,
                    'alamat_email_badan_usaha' => $email,
                    'pjbu' => $pjbu,
                    'data_pengurus_pemegang_saham' => $data_pengurus,
                    'nama_pemegang_saham' => $request->nama_pemegang_saham,
                    'no_hp_pemegang_saham' => $request->no_hp_pemegang_saham,
                    'logo_badan_usaha' => $logo,
                    'status_diterima' => 'pending',
                    'status_aktif' => 'will_expire',
                    'tanggal_diterima' => null,
                    'expired_at' => null,
                    'komentar' => null,
                    'can_reapply' => false,
                    'rejection_reason' => null,
                    'rejection_date' => null,
                ]);

                return response()->json(['message' => 'Pengajuan KTA diperbarui setelah ditolak.', 'kta' => $existingKTA], 200);
            }

            return response()->json(['message' => 'Anda sudah memiliki KTA yang aktif.', 'can_extend' => true], 403);
        }

        // KTA baru
        $data = $request->only(['kabupaten_id', 'rekening_id']);

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $filename = time() . '_' . $request->file($field)->getClientOriginalName();
                $data[$field] = $request->file($field)->storeAs($basePath, $filename);
            }
        }

        $data += [
            'user_id' => $userId,
            'alamat_email_badan_usaha' => $email,
            'pjbu' => $pjbu,
            'data_pengurus_pemegang_saham' => $data_pengurus,
            'nama_pemegang_saham' => $request->nama_pemegang_saham,
            'no_hp_pemegang_saham' => $request->no_hp_pemegang_saham,
            'logo_badan_usaha' => $logo,
            'status_diterima' => 'pending',
            'status_aktif' => 'will_expire',
        ];

        $newKTA = KTA::create($data);

        return response()->json(['message' => 'Pengajuan KTA berhasil dikirim.', 'kta' => $newKTA], 201);
    });
}

  public function extend(Request $request, $id)
{
    $request->validate([
        'bukti_transfer' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $userId = Auth::id(); 
    $kta = KTA::where('id', $id)->where('user_id', $userId)->first();

    if (!$kta) {
        return response()->json(['message' => 'KTA not found or not yours.'], 403);
    }

    if (!$kta->expired_at || now()->diffInDays($kta->expired_at, false) > 30) {
        return response()->json(['message' => 'You can only extend your KTA within 1 month before it expires.'], 403);
    }

    if ($request->hasFile('bukti_transfer')) {
        if ($kta->bukti_transfer && Storage::disk('public')->exists($kta->bukti_transfer)) {
            Storage::disk('public')->delete($kta->bukti_transfer);
        }

        $basePath = "kta/{$userId}";
        $originalName = time() . '_' . $request->file('bukti_transfer')->getClientOriginalName();
        $path = $request->file('bukti_transfer')->storeAs($basePath, $originalName, 'public');

        $kta->update([
            'bukti_transfer' => $path,
            'status_perpanjangan_kta' => 'pending',
        ]);
    }

    return response()->json(['message' => 'KTA extension submitted successfully.'], 200);
}

public function checkDetail()
{
        $userId = Auth::id();
    
        $kta = KTA::where('user_id', $userId)->first();
    
        if (!$kta) {
            return response()->json(['message' => 'KTA not found.'], 404);
        }
        if ($kta->status_diterima !== 'approve' && $kta->status_diterima !== 'rejected') {
            return response()->json(['message' => 'KTA not approved or rejected yet.'], 403);
        }
    
        $response = [
            'KTA ID' => $kta->id,
            'Nomor KTA'=> $kta->no_kta,
            'Status Diterima' => $kta->status_diterima,
            'Status Aktif' => $kta->status_aktif,
            'Tanggal Diterima' => $kta->tanggal_diterima,
            'Tanggal Expired' => $kta->expired_at,
            'Tanggal Dibuat' => $kta->created_at,
            'Tanggal Update' => $kta->updated_at,
        ];
    
        // jika ditolak
        if ($kta->status_kta === 'rejected') {
            $response['komentar'] = $kta->komentar ?? 'Tidak ada komentar.';
            $response['tanggal_ditolak'] = $kta->rejection_date ?? 'Tidak ada tanggal penolakan.';
        }
        
        return response()->json($response, 200);
        
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

  public function downloadFile($userId)
{
    $user = User::find($userId);
    if (!$user) {
        return response()->json(['error' => 'User tidak ditemukan.'], 404);
    }

    $kta = KTA::where('user_id', $userId)->first();
    if (!$kta) {
        return response()->json(['error' => 'Data KTA tidak ditemukan.'], 404);
    }

    // Membuat file biodata (misal: nama perusahaan, nama direktur, dsb)
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();

    $section->addText('Nama Perusahaan: ' . $user->nama_perusahaan);
    $section->addText('Alamat Perusahaan: ' . $user->alamat_perusahaan);
    $section->addText('Nama Direktur: ' . $user->nama_direktur);
    $section->addText('Nama Penanggung Jawab: ' . $user->nama_penanggung_jawab);
    $section->addText('Nomor HP Penanggung Jawab: ' . $user->no_hp_penanggung_jawab);
    $section->addText('Nama Pemegang Saham: ' . $user->nama_pemegang_saham);
    $section->addText('Nomor HP Pemegang Saham: ' . $user->no_hp_pemegang_saham);
    $section->addText('Email Perusahaan: ' . $user->email);
    // Tambahkan biodata lainnya yang diperlukan...

    // Menyimpan dokumen biodata ke dalam file .docx
    $docFilePath = storage_path("app/data_user/{$userId}/KTA/biodata_perusahaan_dan_direktur.docx");
    $phpWord->save($docFilePath);

    // Menyusun file ZIP
    $zipFileName = "{$userId}_kta_documents.zip";
    $zipDirectory = storage_path("app/data_user/{$userId}/KTA");
    $zipFilePath = "{$zipDirectory}/{$zipFileName}";

    // Membuat direktori ZIP jika belum ada
    if (!file_exists($zipDirectory)) {
        mkdir($zipDirectory, 0755, true);
    }

    // Menghapus file ZIP yang lama jika ada
    if (file_exists($zipFilePath)) {
        unlink($zipFilePath);
    }

    // Membuka file ZIP
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
        return response()->json(['error' => 'Gagal membuat file ZIP.'], 500);
    }

    // Menambahkan file biodata ke dalam ZIP
    $zip->addFile($docFilePath, 'biodata_perusahaan_dan_direktur.docx');

    // Menambahkan file lainnya dari folder data_user/{userId}/KTA
    $files = File::allFiles(storage_path("app/data_user/{$userId}"));
    foreach ($files as $file) {
        if ($file->getFilename() !== 'biodata_perusahaan_dan_direktur.docx') { // Jangan duplikasi file biodata
            $zip->addFile($file->getRealPath(), $file->getFilename());
        }
    }

    // Menambahkan file tambahan dari folder detail_user/{userId}
    $detailUserDirectory = storage_path("app/data_user/{$userId}/detail_user");

    // Pastikan folder `detail_user/{userId}` ada
    if (file_exists($detailUserDirectory)) {
        // Menambahkan semua file di dalam subfolder detail_user
        $detailFiles = File::allFiles($detailUserDirectory);
        foreach ($detailFiles as $file) {
            // Membuat struktur direktori dalam ZIP sesuai dengan subfolder yang ada
            $relativePath = 'detail_user/' . $file->getRelativePathname(); // Menjaga struktur direktori
            $zip->addFile($file->getRealPath(), $relativePath);
        }
    }

    // Menutup file ZIP
    $zip->close();

    // Mengunduh file ZIP
    return response()->download($zipFilePath)->deleteFileAfterSend(true);
}

  public function approveKTA(Request $request, $id)
{
    // Validasi manual
    $validator = Validator::make($request->all(), [
        'status_diterima' => 'required|in:approve,rejected',
        'komentar' => 'nullable|string|max:255',
        'no_kta' => 'required_if:status_diterima,approve|nullable|string|unique:kta,no_kta|max:20',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $validated = $validator->validated();

    // Cari data KTA
    $ktaRegistration = KTA::find($id);
    if (!$ktaRegistration) {
        return response()->json([
            'success' => false,
            'message' => 'KTA registration not found.',
        ], 404);
    }

    if ($validated['status_diterima'] === 'approve') {
        if ($ktaRegistration->status_diterima === 'approve') {
            return response()->json([
                'success' => false,
                'message' => 'KTA registration has been previously approved.',
            ], 400);
        }

        $ktaRegistration->update([
            'status_diterima' => 'approve',
            'tanggal_diterima' => now(),
            'status_aktif' => 'active',
            'expired_at' => Carbon::now()->addYears(1),
            'can_reapply' => false,
            'komentar' => null,
            'rejection_date' => null,
            'no_kta' => $validated['no_kta'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'KTA registration has been successfully approved.',
            'data' => $ktaRegistration,
        ], 200);
    }

    if ($validated['status_diterima'] === 'rejected') {
        if ($ktaRegistration->status_diterima === 'approve' && $ktaRegistration->status_aktif === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'KTA registration that has been approved and active cannot be rejected.',
            ], 400);
        }

        $userId = $ktaRegistration->user_id;
        $userDirectory = storage_path("app/kta/{$userId}");

        if (is_dir($userDirectory)) {
            $files = glob($userDirectory . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            @rmdir($userDirectory);
        }

        $ktaRegistration->update([
            'status_diterima' => 'rejected',
            'status_aktif' => null,
            'komentar' => $validated['komentar'],
            'rejection_date' => now(),
            'can_reapply' => true,
            'akta_pendirian' => null,
            'npwp_perusahaan' => null,
            'nib' => null,
            'pjbu' => null,
            'data_pengurus_pemegang_saham' => null,
            'bukti_transfer' => null,
            'logo_badan_usaha' => null,
            'no_kta' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'KTA registration has been rejected. Documents have been deleted.',
        ], 200);
    }

    // Default fallback (tidak akan dijalankan karena sudah divalidasi)
    return response()->json([
        'success' => false,
        'message' => 'Invalid status.',
    ], 400);
}


public function allPending()
{
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
        ->where('kta.status_diterima', 'pending')
        ->orderBy('kta.created_at', 'desc')
        ->get();

    if ($registrants->isEmpty()) {
        return response()->json([
            'success' => true,
            'message' => 'Tidak ada data pendaftaran dengan status pending.',
            'data' => [],
        ], 200);
    }

    return response()->json([
        'success' => true,
        'message' => 'Data pendaftaran pending berhasil diambil.',
        'data' => $registrants,
    ], 200);
}

  
public function index()
{
    $ktas = KTA::select(
            'kta.id',
            'kta.user_id',
            'users.nama_perusahaan',
            'users.nama_direktur',
            'users.nama_penanggung_jawab',
            'users.alamat_perusahaan',
            'users.email',
            'kta.kabupaten_id',
            'kta.no_kta',
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
        ->whereIn('kta.status_aktif', ['active', 'will_expire']) // Perbaikan disini
        ->get();

    return response()->json([
        'success' => true,
        'message' => 'Data KTA yang aktif atau akan kedaluwarsa berhasil diambil.',
        'data' => $ktas
    ], 200);
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
      ->where('kta.id', $id)
      ->where('kta.status_diterima', 'approve')
      ->where('kta.status_aktif', 'active', 'will_expire')
      ->first();
  
      if (!$kta) {
          return response()->json(['message' => 'KTA not found or not approved'], 404);
      }
  
      return response()->json($kta, 200);
}
  
  public function search(Request $request)
{
    $searchTerm = $request->input('search');

    if (empty($searchTerm)) {
        return response()->json(['message' => 'Search term is required'], 400);
    }

    $query = KTA::select(
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
        ->leftJoin('kota_kabupaten', 'kta.kabupaten_id', '=', 'kota_kabupaten.id')
        ->where('kta.status_diterima', 'approve')
        ->where(function ($q) use ($searchTerm) {
            $q->where('users.nama_perusahaan', 'like', '%' . $searchTerm . '%')
              ->orWhere('users.nama_direktur', 'like', '%' . $searchTerm . '%')
              ->orWhere('users.alamat_perusahaan', 'like', '%' . $searchTerm . '%')
              ->orWhere('users.email', 'like', '%' . $searchTerm . '%');
        });

    if ($request->has('status_aktif')) {
        $query->where('kta.status_aktif', $request->input('status_aktif'));
    }

    $ktas = $query->orderByDesc('kta.tanggal_diterima')->get();

    if ($ktas->isEmpty()) {
        return response()->json(['message' => 'Data KTA tidak ditemukan'], 404);
    }

    return response()->json($ktas, 200);
}

  public function uploadKta(Request $request, $userId)
{
    $user = User::find($userId);

    // Cek apakah user ada
    if (!$user) {
        return response()->json([
            'message' => 'User tidak ditemukan.'
        ], 404);
    }

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

    if (!$request->hasFile('kta_file')) {
        return response()->json([
            'message' => 'Tidak ada file yang diunggah.'
        ], 400);
    }

    $file = $request->file('kta_file');

    // Simpan file
    $folderPath = "kta(CardFromDPP)/{$userId}";
    $fileName = 'kta_' . time() . '.' . $file->getClientOriginalExtension();
    $filePath = $file->storeAs($folderPath, $fileName, 'public');

    // Cek apakah penyimpanan gagal
    if (!$filePath) {
        return response()->json([
            'message' => 'Gagal menyimpan file.'
        ], 500);
    }

    // Update atau buat data KTA
    $kta = KTA::updateOrCreate(
        ['user_id' => $userId],
        ['kta_file' => $filePath]
    );

    return response()->json([
        'message' => 'KTA berhasil diunggah.',
        'file_path' => $filePath
    ], 200);
}
}
