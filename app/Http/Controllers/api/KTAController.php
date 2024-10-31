<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KTA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KtaController extends Controller
{
  // Metode untuk menyimpan KTA baru
  public function store(Request $request)
  {
    // Validasi input
    $request->validate([
      'formulir_permohonan' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'pernyataan_kebenaran' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'pengesahan_menkumham' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'akta_pendirian' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
      'akta_perubahan' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
      'npwp_perusahaan' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'surat_domisili' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'ktp_pengurus' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'logo' => 'nullable|file|mimes:jpg,jpeg,png',
      'foto_direktur' => 'required|file|mimes:jpg,jpeg,png',
      'npwp_pengurus_akta' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'bukti_transfer' => 'required|file|mimes:pdf,jpg,jpeg,png',
      'kabupaten_id' => 'required|exists:kota_kabupaten,id',
    ]);

    // Simpan dokumen dan dapatkan pathnya
    $paths = [];
    $files = [
      'formulir_permohonan',
      'pernyataan_kebenaran',
      'pengesahan_menkumham',
      'akta_pendirian',
      'akta_perubahan',
      'npwp_perusahaan',
      'surat_domisili',
      'ktp_pengurus',
      'logo',
      'foto_direktur',
      'npwp_pengurus_akta',
      'bukti_transfer'
    ];

    foreach ($files as $file) {
      if ($request->hasFile($file)) {
        $paths[$file] = $request->file($file)->store('uploads/kta', 'public');
      }
    }

    // Buat KTA baru
    try {
      $kta = KTA::create(array_merge($paths, [
        'kabupaten_id' => $request->kabupaten_id,
        'user_id' => Auth::id(),
        'status_perpanjangan_kta' => 'pending', // Status awal
        'tanggal_diterima' => null, // Belum ada tanggal diterima saat dibuat
      ]));

      return response()->json([
        'message' => 'KTA berhasil diajukan.',
        'kta' => $kta,
      ], 201);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Gagal menyimpan KTA: ' . $e->getMessage(),
      ], 500);
    }
  }

  // Metode untuk memperbarui KTA
  public function update(Request $request, KTA $kta)
  {
    // Validasi input untuk update
    $request->validate([
      'status' => 'nullable|in:accepted,rejected', // Untuk status KTA
      'status_perpanjangan_kta' => 'nullable|in:active,inactive,pending,rejected', // Untuk perpanjangan KTA
      'komentar' => 'nullable|string',
    ]);

    // Update status KTA jika diberikan
    if ($request->has('status')) {
      // Set tanggal diterima saat status menjadi aktif
      if ($request->status == 'active') {
        $kta->tanggal_diterima = now(); // Set tanggal diterima
      }
      $kta->status = $request->status; // Update status KTA
    }

    // Update status perpanjangan KTA jika diberikan
    if ($request->has('status_perpanjangan_kta')) {
      if ($request->status_perpanjangan_kta == 'rejected') {
        // Simpan komentar jika ditolak
        $kta->komentar = $request->komentar;
      }
      $kta->status_perpanjangan_kta = $request->status_perpanjangan_kta; // Update status perpanjangan KTA
    }

    // Simpan perubahan ke database
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

    // Proses upload bukti transfer
    if ($request->hasFile('bukti_transfer')) {
      $file = $request->file('bukti_transfer');
      $filename = time() . '.' . $file->getClientOriginalExtension();
      $file->storeAs('uploads/bukti_transfer', $filename, 'public');
      $kta->bukti_transfer = $filename; // Simpan nama file
    }

    // Ubah status perpanjangan KTA menjadi pending
    $kta->status_perpanjangan_kta = 'pending';
    $kta->save();

    return response()->json(['message' => 'Perpanjangan KTA diajukan.']);
  }

  // Metode untuk menampilkan semua pengajuan KTA untuk admin
  public function index()
  {
    $ktas = KTA::with('user')->get(); // Menampilkan semua KTA dengan relasi user
    return response()->json($ktas);
  }

  // Metode untuk menampilkan detail KTA tertentu
  public function show($id)
  {
    // Temukan KTA berdasarkan ID dan muat relasi 'user'
    $kta = KTA::with('user')->findOrFail($id);

    // Ambil data pengguna yang mengajukan KTA
    $user = $kta->user;

    // Siapkan data untuk ditampilkan
    $response = [
      'kta' => [
        'id' => $kta->id,
        'tanggal_diterima' => $kta->tanggal_diterima,
        'status_perpanjangan_kta' => $kta->status_perpanjangan_kta,
        'formulir_permohonan' => $kta->formulir_permohonan,
        'pernyataan_kebenaran' => $kta->pernyataan_kebenaran,
        'pengesahan_menkumham' => $kta->pengesahan_menkumham,
        'akta_pendirian' => $kta->akta_pendirian,
        'akta_perubahan' => $kta->akta_perubahan,
        'npwp_perusahaan' => $kta->npwp_perusahaan,
        'surat_domisili' => $kta->surat_domisili,
        'ktp_pengurus' => $kta->ktp_pengurus,
        'logo' => $kta->logo,
        'foto_direktur' => $kta->foto_direktur,
        'npwp_pengurus_akta' => $kta->npwp_pengurus_akta,
        'bukti_transfer' => $kta->bukti_transfer,
        'kabupaten_id' => $kta->kabupaten_id,
        'tanggal_pengajuan' => $kta->created_at,
        'updated_at' => $kta->updated_at,
      ],
      'user' => [
        'id' => $user->id,
        'nama_perusahaan' => $user->nama_perusahaan,
        'nama_direktur' => $user->nama_direktur,
        'email' => $user->email,
        'no_telp' => $user->no_telp, // Contoh field tambahan
        'alamat' => $user->alamat, // Contoh field tambahan
        'tanggal_pengajuan' => $kta->created_at,
        // Tambahkan informasi lain yang ingin ditampilkan
      ],
    ];

    return response()->json($response);
  }
}
