<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KTA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KtaController extends Controller
{
  // Metode untuk menyimpan KTA baru
  public function store(Request $request)
  {
    // Validasi data KTA yang diajukan
    $request->validate([
      'formulir_permohonan' => 'required|file',
      'pernyataan_kebenaran' => 'required|file',
      'pengesahan_menkumham' => 'required|file',
      'akta_pendirian' => 'required|file',
      'akta_perubahan' => 'nullable|file',
      'npwp_perusahaan' => 'required|file',
      'surat_domisili' => 'required|file',
      'ktp_pengurus' => 'required|file',
      'logo' => 'nullable|file',
      'foto_direktur' => 'nullable|file',
      'npwp_pengurus_akta' => 'required|file',
      'bukti_transfer' => 'required|file',
      'kabupaten_id' => 'required|integer',
    ]);

    // Buat entri KTA baru dengan user_id dari token autentikasi
    $kta = KTA::create([
      'formulir_permohonan' => $request->file('formulir_permohonan')->store('documents'),
      'pernyataan_kebenaran' => $request->file('pernyataan_kebenaran')->store('documents'),
      'pengesahan_menkumham' => $request->file('pengesahan_menkumham')->store('documents'),
      'akta_pendirian' => $request->file('akta_pendirian')->store('documents'),
      'akta_perubahan' => $request->file('akta_perubahan') ? $request->file('akta_perubahan')->store('documents') : null,
      'npwp_perusahaan' => $request->file('npwp_perusahaan')->store('documents'),
      'surat_domisili' => $request->file('surat_domisili')->store('documents'),
      'ktp_pengurus' => $request->file('ktp_pengurus')->store('documents'),
      'logo' => $request->file('logo') ? $request->file('logo')->store('images') : null,
      'foto_direktur' => $request->file('foto_direktur') ? $request->file('foto_direktur')->store('images') : null,
      'npwp_pengurus_akta' => $request->file('npwp_pengurus_akta')->store('documents'),
      'bukti_transfer' => $request->file('bukti_transfer')->store('payments'),
      'kabupaten_id' => $request->kabupaten_id,
      'user_id' => auth()->id(),
    ]);

    return response()->json(['message' => 'Pengajuan KTA berhasil disubmit', 'kta' => $kta], 201);
  }


  // Metode untuk memperbarui status KTA atau perpanjangan
  public function update(Request $request, KTA $kta)
  {
    $request->validate([
      'status' => 'nullable|in:accepted,rejected',
      'status_perpanjangan_kta' => 'nullable|in:active,inactive,pending,rejected',
      'komentar' => 'nullable|string',
    ]);

    // Update status KTA (pendaftaran) jika diberikan
    if ($request->has('status')) {
      $kta->status = $request->status;
      if ($request->status == 'accepted') {
        $kta->tanggal_diterima = now();
      } elseif ($request->status == 'rejected') {
        $kta->komentar = $request->komentar;
      }
    }

    // Update status perpanjangan KTA jika diberikan
    if ($request->has('status_perpanjangan_kta')) {
      $kta->status_perpanjangan_kta = $request->status_perpanjangan_kta;
      if ($request->status_perpanjangan_kta == 'rejected') {
        $kta->komentar = $request->komentar;
      }
    }

    $kta->save();

    return response()->json([
      'message' => 'Status KTA berhasil diperbarui.',
      'kta' => $kta,
    ]);
  }

  // Metode untuk mengajukan perpanjangan KTA
  public function extend(Request $request, $id)
  {
    $request->validate([
      'bukti_transfer' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
    ]);

    $kta = KTA::findOrFail($id);

    if ($request->hasFile('bukti_transfer')) {
      $file = $request->file('bukti_transfer');
      $filename = time() . '.' . $file->getClientOriginalExtension();
      $file->storeAs('uploads/bukti_transfer', $filename, 'public');
      $kta->bukti_transfer = $filename;
    }

    $kta->status_perpanjangan_kta = 'pending';
    $kta->save();

    return response()->json(['message' => 'Perpanjangan KTA diajukan.']);
  }

  // Cek masa aktif KTA dan update jika sudah 1 tahun
  public function checkExpiry()
  {
    $ktas = KTA::where('status', 'accepted')->get();

    foreach ($ktas as $kta) {
      if ($kta->tanggal_diterima && Carbon::parse($kta->tanggal_diterima)->addYear()->isPast()) {
        $kta->status_perpanjangan_kta = 'inactive';
        $kta->save();
      }
    }

    return response()->json(['message' => 'Masa aktif KTA diperiksa dan diperbarui.']);
  }

  public function index()
  {
    $ktas = KTA::with('user')->get();
    return response()->json($ktas);
  }

  public function show($id)
  {
    $kta = KTA::find($id);

    if (!$kta) {
      return response()->json(['message' => 'KTA not found'], 404);
    }

    return response()->json($kta, 200);
  }
}
