<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SBUNRegistrations;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SbunRegistrationController extends Controller
{
  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'akta_pendirian' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'npwp_perusahaan' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'ktp_penanggung_jawab' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'npwp_penanggung_jawab' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'foto_penanggung_jawab' => 'required|file|mimes:jpg,png|max:2048',
      'nomor_hp_penanggung_jawab' => 'required|numeric',
      'ktp_pemegang_saham' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'npwp_pemegang_saham' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'email_perusahaan' => 'required|email',
      'logo_perusahaan' => 'required|file|mimes:jpg,png,pdf|max:2048',
      'non_konstruksi_klasifikasi_id' => 'required|integer',
      'non_konstruksi_sub_klasifikasi_id' => 'required|integer',
      'bukti_transfer' => 'required|file|mimes:jpg,png,pdf|max:2048',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 422);
    }

    $data = $request->except([
      'akta_pendirian',
      'npwp_perusahaan',
      'ktp_penanggung_jawab',
      'npwp_penanggung_jawab',
      'foto_penanggung_jawab',
      'ktp_pemegang_saham',
      'npwp_pemegang_saham',
      'logo_perusahaan',
      'bukti_transfer',
    ]);

    $data['user_id'] = auth()->id();

    $fileFields = [
      'akta_pendirian',
      'npwp_perusahaan',
      'ktp_penanggung_jawab',
      'npwp_penanggung_jawab',
      'foto_penanggung_jawab',
      'ktp_pemegang_saham',
      'npwp_pemegang_saham',
      'logo_perusahaan',
      'bukti_transfer',
    ];

    foreach ($fileFields as $field) {
      if ($request->hasFile($field)) {
        $data[$field] = $request->file($field)->store('uploads/sbus', 'public');
      }
    }

    $registration = SBUNRegistrations::create($data);

    return response()->json($registration->load(['user', 'non_konstruksi_klasifikasi', 'non_konstruksi_sub_klasifikasi']), 201);
  }

  public function show($id)
  {
    $registration = SBUNRegistrations::with(['non_konstruksi_klasifikasi', 'non_konstruksi_sub_klasifikasi', 'user'])->find($id);

    if (!$registration) {
      return response()->json(['message' => 'Pendaftaran tidak ditemukan'], 404);
    }

    return response()->json($registration);
  }

  public function status(Request $request, $id)
  {
    $validator = Validator::make($request->all(), [
      'approval_status' => 'required|in:approved,rejected',
      'admin_comment' => 'required_if:approval_status,rejected|string',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 422);
    }

    $registration = SBUNRegistrations::findOrFail($id);

    if ($registration->expired_at && $registration->expired_at->isPast()) {
      $registration->status_aktif = 'inactive';
    }

    if ($request->approval_status === 'rejected') {
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

      foreach ($fileFields as $field) {
        if ($registration->$field) {
          Storage::disk('public')->delete($registration->$field);
        }
      }

      $registration->delete();

      return response()->json(['message' => 'Pendaftaran berhasil dihapus'], 200);
    } else {
      $registration->update([
        'approval_status' => 'approved',
        'admin_comment' => null,
        'status_aktif' => 'active',
        'expired_at' => now()->addYears(2),
      ]);

      return response()->json([
        'message' => 'Pendaftaran berhasil disetujui.',
        'registration' => $registration->load(['user', 'non_konstruksi_klasifikasi', 'non_konstruksi_sub_klasifikasi']),
      ], 200);
    }
  }

  public function index()
  {
    $registrations = SBUNRegistrations::with('user', 'non_konstruksi_klasifikasi', 'non_konstruksi_sub_klasifikasi')->get();
    return response()->json($registrations);
  }

  public function search(Request $request)
  {
    $searchTerm = $request->input('search');

    $registrations = SBUNRegistrations::where('approval_status', 'approved')
      ->whereHas('user', function ($query) use ($searchTerm) {
        $query->where('nama_perusahaan', 'like', '%' . $searchTerm . '%')
          ->orWhere('email', 'like', '%' . $searchTerm . '%');
      })
      ->get();

    if ($registrations->isEmpty()) {
      return response()->json(['message' => 'SBU tidak ditemukan.'], 404);
    }

    return response()->json($registrations, 200);
  }
}
