<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Klasifikasi;
use Illuminate\Support\Facades\DB;

class KlasifikasiController extends Controller
{
  // Menampilkan semua klasifikasi beserta sub klasifikasi dan kodenya
  public function detail()
  {
    // Query untuk join tabel klasifikasi dan sub klasifikasi
    $klasifikasis = DB::table('klasifikasis')
      ->leftJoin('sub_klasifikasis', 'klasifikasis.id', '=', 'sub_klasifikasis.klasifikasi_id')
      ->select(
        'klasifikasis.id as klasifikasi_id',
        'klasifikasis.nama as klasifikasi_nama',
        'sub_klasifikasis.id as sub_klasifikasi_id',
        'sub_klasifikasis.nama as sub_klasifikasi_nama'
      )
      ->get();

    // Jika data kosong
    if ($klasifikasis->isEmpty()) {
      return response()->json([
        'success' => false,
        'message' => 'No classifications found.',
      ], 404);
    }

    // Group data berdasarkan klasifikasi_id
    $groupedData = $klasifikasis->groupBy('klasifikasi_id')->map(function ($group) {
      return [
        'id' => $group->first()->klasifikasi_id,
        'nama' => $group->first()->klasifikasi_nama,
        'sub_klasifikasis' => $group->map(function ($item) {
          if ($item->sub_klasifikasi_id) {
            return [
              'id' => $item->sub_klasifikasi_id,
              'nama' => $item->sub_klasifikasi_nama,
            ];
          }
        })->filter()->values(),
      ];
    })->values();

    // Mengembalikan response
    return response()->json([
      'success' => true,
      'data' => $groupedData,
    ], 200);
  }

  // Menampilkan semua klasifikasi
  public function index()
  {
    $klasifikasis = Klasifikasi::all();

    return response()->json([
      'success' => true,
      'data' => $klasifikasis,
    ], 200);
  }

  // Menambahkan klasifikasi baru
  public function store(Request $request)
  {
    $validated = $request->validate([
      'nama' => 'required|string|max:255',
    ]);

    $klasifikasi = Klasifikasi::create($validated);

    return response()->json([
      'success' => true,
      'message' => 'Klasifikasi berhasil ditambahkan',
      'data' => $klasifikasi,
    ], 201);
  }

  // Menampilkan detail klasifikasi berdasarkan ID
  public function show($id)
  {
    $klasifikasi = Klasifikasi::with('subKlasifikasis')->find($id);

    if (!$klasifikasi) {
      return response()->json([
        'success' => false,
        'message' => 'Klasifikasi tidak ditemukan',
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data' => $klasifikasi,
    ]);
  }

  // Mengupdate klasifikasi berdasarkan ID
  public function update(Request $request, $id)
  {
    $klasifikasi = Klasifikasi::find($id);

    if (!$klasifikasi) {
      return response()->json([
        'success' => false,
        'message' => 'Klasifikasi tidak ditemukan',
      ], 404);
    }

    $validated = $request->validate([
      'nama' => 'sometimes|required|string|max:255',
    ]);

    $klasifikasi->update($validated);

    return response()->json([
      'success' => true,
      'message' => 'Klasifikasi berhasil diupdate',
      'data' => $klasifikasi,
    ]);
  }

  // Menghapus klasifikasi berdasarkan ID
  public function destroy($id)
  {
    $klasifikasi = Klasifikasi::find($id);

    if (!$klasifikasi) {
      return response()->json([
        'success' => false,
        'message' => 'Klasifikasi tidak ditemukan',
      ], 404);
    }

    $klasifikasi->delete();

    return response()->json([
      'success' => true,
      'message' => 'Klasifikasi berhasil dihapus',
    ]);
  }
}
