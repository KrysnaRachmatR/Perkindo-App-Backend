<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Klasifikasi;
use App\Models\SubKlasifikasi;
use App\Models\SbuCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KlasifikasiController extends Controller
{
  // Menampilkan semua klasifikasi
  public function index()
  {
    return Klasifikasi::all();
  }

  // Menambahkan klasifikasi baru
  public function storeWithDetails(Request $request)
  {
    $request->validate([
      'nama_klasifikasi' => 'required|string|unique:klasifikasis,nama_klasifikasi',
      'sub_klasifikasis' => 'required|array',
      'sub_klasifikasis.*.nama_sub_klasifikasi' => 'required|string',
      'sub_klasifikasis.*.kode_sbu' => 'required|string|unique:sbu_codes,kode_sbu',
      'sub_klasifikasis.*.kbli' => 'required|string',
    ]);

    DB::beginTransaction();
    try {
      // Buat Klasifikasi baru
      $klasifikasi = Klasifikasi::create([
        'nama_klasifikasi' => $request->nama_klasifikasi,
      ]);

      // Iterasi untuk menambahkan Sub Klasifikasi beserta Kode SBU
      foreach ($request->sub_klasifikasis as $sub) {
        $subKlasifikasi = SubKlasifikasi::create([
          'klasifikasi_id' => $klasifikasi->id,
          'nama_sub_klasifikasi' => $sub['nama_sub_klasifikasi'],
        ]);

        SbuCode::create([
          'sub_klasifikasi_id' => $subKlasifikasi->id,
          'kode_sbu' => $sub['kode_sbu'],
          'kbli' => $sub['kbli'],
        ]);
      }

      DB::commit();
      return response()->json([
        'message' => 'Klasifikasi, Sub Klasifikasi, dan Kode SBU berhasil ditambahkan.',
        'klasifikasi' => $klasifikasi->load('subKlasifikasis.sbuCode'),
      ], 201);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'message' => 'Gagal menambahkan klasifikasi.',
        'error' => $e->getMessage(),
      ], 500);
    }
  }


  // Menampilkan detail klasifikasi berdasarkan ID
  public function show($id)
  {
    return Klasifikasi::findOrFail($id);
  }

  // Update klasifikasi
  public function update(Request $request, $id)
  {
    $request->validate([
      'nama_klasifikasi' => 'required|string|unique:klasifikasis,nama_klasifikasi,' . $id,
    ]);

    $klasifikasi = Klasifikasi::findOrFail($id);
    $klasifikasi->update($request->all());

    return response()->json($klasifikasi, 200);
  }

  // Hapus klasifikasi berdasarkan ID
  public function destroy($id)
  {
    Klasifikasi::destroy($id);
    return response()->json(null, 204);
  }

  // Menambahkan sub klasifikasi dengan kode SBU dan KBLI
  public function addSubKlasifikasiWithSbu(Request $request, $klasifikasiId)
  {
    $request->validate([
      'nama_sub_klasifikasi' => 'required|string|max:255',
      'kode_sbu' => 'required|string|unique:sbu_codes,kode_sbu',
      'kbli' => 'required|string|max:10',
    ]);

    DB::beginTransaction();

    try {
      // Pastikan klasifikasi ditemukan
      $klasifikasi = Klasifikasi::findOrFail($klasifikasiId);

      // Buat sub klasifikasi baru
      $subKlasifikasi = SubKlasifikasi::create([
        'klasifikasi_id' => $klasifikasi->id,
        'nama_sub_klasifikasi' => $request->nama_sub_klasifikasi,
      ]);

      // Tambahkan kode SBU dan KBLI untuk sub klasifikasi tersebut
      $sbuCode = SbuCode::create([
        'sub_klasifikasi_id' => $subKlasifikasi->id,
        'kode_sbu' => $request->kode_sbu,
        'kbli' => $request->kbli,
      ]);

      DB::commit();

      return response()->json([
        'message' => 'Sub Klasifikasi dan Kode SBU berhasil ditambahkan.',
        'sub_klasifikasi' => $subKlasifikasi,
        'sbu_code' => $sbuCode,
      ], 201);
    } catch (\Exception $e) {
      DB::rollBack();

      return response()->json([
        'message' => 'Gagal menambahkan sub klasifikasi dan kode SBU.',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  public function klasifikasiWithDetails()
  {
    $data = Klasifikasi::with(['subKlasifikasis.sbuCode'])->get();

    return response()->json($data, 200);
  }
}
