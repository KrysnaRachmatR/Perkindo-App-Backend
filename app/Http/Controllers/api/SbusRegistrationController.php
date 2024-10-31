<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SbusRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SbusRegistrationController extends Controller
{
  // Menampilkan semua pendaftaran SBU
  public function index()
  {
    $registrations = SbusRegistration::all();
    return response()->json($registrations);
  }

  // Menyimpan pendaftaran SBU baru
  public function store(Request $request)
  {
    // Validasi input
    $validator = Validator::make($request->all(), [
      'akta_asosiasi_aktif_masa_berlaku' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'akta_perusahaan_pendirian' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'akta_perubahan' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
      'pengesahan_menkumham' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'nib_berbasis_resiko' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'ktp_pengurus' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'npwp_pengurus' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'skk' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'ijazah_legalisir' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'PJTBU' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'PJKSBU' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'email_perusahaan' => 'required|email',
      'kop_perusahaan' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'nomor_whatsapp' => 'required|string',
      'foto_pas_direktur' => 'required|file|mimes:jpg,png|max:2048',
      'surat_pernyataan_tanggung_jawab_mutlak' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'surat_pernyataan_SMAP' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'lampiran_tkk' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'neraca_keuangan_2_tahun_terakhir' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'akun_oss' => 'required|string',
      'klasifikasi_id' => 'required|exists:klasifikasis,id'
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 422);
    }

    // Simpan file dan buat pendaftaran SBU
    $data = $request->all();
    $data['status'] = 'pending'; // Status default

    // Simpan file yang diupload
    foreach ($request->files as $key => $file) {
      $data[$key] = $file->store('uploads/sbus', 'public');
    }

    $registration = SbusRegistration::create($data);

    return response()->json($registration, 201);
  }

  // Menampilkan detail pendaftaran SBU berdasarkan ID
  public function show($id)
  {
    $registration = SbusRegistration::find($id);

    if (!$registration) {
      return response()->json(['message' => 'Pendaftaran tidak ditemukan'], 404);
    }

    return response()->json($registration);
  }

  // Mengupdate pendaftaran SBU
  public function update(Request $request, $id)
  {
    $registration = SbusRegistration::find($id);

    if (!$registration) {
      return response()->json(['message' => 'Pendaftaran tidak ditemukan'], 404);
    }

    // Validasi input
    $validator = Validator::make($request->all(), [
      'status' => 'required|in:accepted,pending,rejected',
      'komentar' => 'nullable|string'
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 422);
    }

    // Update status dan komentar admin
    $registration->status = $request->status;
    if ($request->has('komentar')) {
      // Anda dapat menyimpan komentar ke field lain atau menambahkannya ke model
      $registration->komentar = $request->komentar; // Pastikan ada kolom komentar di migrasi
    }

    $registration->save();

    return response()->json($registration);
  }

  // Menghapus pendaftaran SBU
  public function destroy($id)
  {
    $registration = SbusRegistration::find($id);

    if (!$registration) {
      return response()->json(['message' => 'Pendaftaran tidak ditemukan'], 404);
    }

    $registration->delete();

    return response()->json(['message' => 'Pendaftaran berhasil dihapus']);
  }
}
