<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Klasifikasi;

class KlasifikasiController extends Controller
{
  // Menampilkan semua klasifikasi beserta sub klasifikasi dan kodenya
  public function indexWithSubKlasifikasiAndCodes()
  {
    // Mengambil semua klasifikasi beserta sub klasifikasi
    $klasifikasis = Klasifikasi::with('subKlasifikasis')->get();

    return response()->json([
      'success' => true,
      'data' => $klasifikasis,
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
  public function storeWithDetails(Request $request)
  {
    $validated = $request->validate([
      'nama' => 'required|string|max:255',
    ]);

    $klasifikasi = Klasifikasi::create($validated);

    return response()->json([
      'success' => true,
      'data' => $klasifikasi,
    ], 201);
  }

  // Menampilkan detail klasifikasi berdasarkan ID
  public function show($id)
  {
    $klasifikasi = Klasifikasi::with('subKlasifikasis')->findOrFail($id);

    return response()->json([
      'success' => true,
      'data' => $klasifikasi,
    ]);
  }

  // Mengupdate klasifikasi berdasarkan ID
  public function update(Request $request, $id)
  {
    $klasifikasi = Klasifikasi::findOrFail($id);

    $validated = $request->validate([
      'nama' => 'sometimes|required|string|max:255',
    ]);

    $klasifikasi->update($validated);

    return response()->json([
      'success' => true,
      'data' => $klasifikasi,
    ]);
  }

  // Menghapus klasifikasi berdasarkan ID
  public function destroy($id)
  {
    Klasifikasi::destroy($id);

    return response()->json([
      'success' => true,
      'message' => 'Klasifikasi berhasil dihapus',
    ]);
  }

  // Menambahkan sub klasifikasi ke klasifikasi yang ada
  public function addSubKlasifikasiWithSbu(Request $request, $id)
  {
    $validated = $request->validate([
      'nama' => 'required|string|max:255',
      'sbu_code' => 'required|string|max:255', // Validasi untuk sbu_code
    ]);

    $klasifikasi = Klasifikasi::findOrFail($id);
    $subKlasifikasi = $klasifikasi->subKlasifikasis()->create($validated);

    return response()->json([
      'success' => true,
      'data' => $subKlasifikasi,
    ], 201);
  }

  // Menampilkan sub klasifikasi dari klasifikasi berdasarkan ID
  public function getSubKlasifikasis($id)
  {
    $klasifikasi = Klasifikasi::with('subKlasifikasis')->findOrFail($id);

    return response()->json([
      'success' => true,
      'data' => $klasifikasi->subKlasifikasis,
    ]);
  }
}
