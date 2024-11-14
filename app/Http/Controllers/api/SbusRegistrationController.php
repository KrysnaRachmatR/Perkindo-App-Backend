<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SBURegistrations;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SbusRegistrationController extends Controller
{
  public function index()
  {
    $registrations = SBURegistrations::with('user', 'klasifikasi', 'subKlasifikasi')->get();
    return response()->json($registrations);
  }

  public function store(Request $request)
  {
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
      'akun_oss' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'rekening_id' => 'required|integer',
      'klasifikasi_id' => 'required|exists:klasifikasis,id',
      'sub_klasifikasi_id' => 'required|exists:sub_klasifikasis,id',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 422);
    }

    $data = $request->all();
    $data['user_id'] = auth()->id();
    $fileFields = [
      'akta_asosiasi_aktif_masa_berlaku',
      'akta_perusahaan_pendirian',
      'akta_perubahan',
      'pengesahan_menkumham',
      'nib_berbasis_resiko',
      'ktp_pengurus',
      'npwp_pengurus',
      'skk',
      'ijazah_legalisir',
      'PJTBU',
      'PJKSBU',
      'kop_perusahaan',
      'foto_pas_direktur',
      'surat_pernyataan_tanggung_jawab_mutlak',
      'surat_pernyataan_SMAP',
      'lampiran_tkk',
      'neraca_keuangan_2_tahun_terakhir',
      'akun_oss'
    ];

    foreach ($fileFields as $field) {
      if ($request->hasFile($field)) {
        $data[$field] = $request->file($field)->store('uploads/sbus', 'public');
      }
    }

    $registration = SBURegistrations::create($data);
    return response()->json($registration->load(['user', 'klasifikasi', 'subKlasifikasi']), 201);
  }

  public function show($id)
  {
    $registration = SBURegistrations::with('klasifikasi', 'user')->find($id);

    if (!$registration) {
      return response()->json(['message' => 'Pendaftaran tidak ditemukan'], 404);
    }

    return response()->json($registration);
  }

  public function destroy($id)
  {
    $registration = SBURegistrations::find($id);

    if (!$registration) {
      return response()->json(['message' => 'Pendaftaran tidak ditemukan'], 404);
    }

    $registration->delete();
    return response()->json(['message' => 'Pendaftaran berhasil dihapus']);
  }

  public function status(Request $request, $id)
  {
    $validator = Validator::make($request->all(), [
      'approval_status' => 'required|in:approved,rejected',
      'admin_comment' => 'required_if:approval_status,rejected|string'
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 422);
    }

    $registration = SBURegistrations::findOrFail($id);
    if ($registration->expired_at && $registration->expired_at < now()) {
      $registration->status_aktif = 'inactive';
      $registration->save();
    }

    if ($request->approval_status === 'rejected') {
      $files = [
        'akta_asosiasi_aktif_masa_berlaku',
        'akta_perusahaan_pendirian',
        'akta_perubahan',
        'pengesahan_menkumham',
        'nib_berbasis_resiko',
        'ktp_pengurus',
        'npwp_pengurus',
        'skk',
        'ijazah_legalisir',
        'PJTBU',
        'PJKSBU',
        'kop_perusahaan',
        'foto_pas_direktur',
        'surat_pernyataan_tanggung_jawab_mutlak',
        'surat_pernyataan_SMAP',
        'lampiran_tkk',
        'neraca_keuangan_2_tahun_terakhir',
        'akun_oss'
      ];

      foreach ($files as $file) {
        if ($registration->$file && file_exists(public_path($registration->$file))) {
          unlink(public_path($registration->$file));
        }
      }

      $registration->delete();
      return response()->json([
        'message' => 'Pendaftaran SBU telah ditolak dan data dihapus.',
      ], 200);
    } else {
      $registration->approval_status = 'approved';
      $registration->admin_comment = null;
      $registration->status_aktif = 'active';
      $registration->expired_at = now()->addYears(2);
      $registration->save();

      return response()->json([
        'message' => 'Pendaftaran SBU berhasil disetujui.',
        'registration' => $registration->load(['user', 'klasifikasi', 'subKlasifikasi'])
      ], 200);
    }
  }


  public function search(Request $request)
  {
    $searchTerm = $request->input('search');

    $ktas = SBURegistrations::where('approval_status', 'approved')
      ->whereHas('user', function ($query) use ($searchTerm) {
        $query->where('nama_perusahaan', 'like', '%' . $searchTerm . '%')
          ->orWhere('alamat_perusahaan', 'like', '%' . $searchTerm . '%')
          ->orWhere('email', 'like', '%' . $searchTerm . '%');
      })
      ->get();

    if ($ktas->isEmpty()) {
      return response()->json([
        'success' => false,
        'message' => 'SBU tidak ditemukan.'
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data' => $ktas
    ], 200);
  }
}
