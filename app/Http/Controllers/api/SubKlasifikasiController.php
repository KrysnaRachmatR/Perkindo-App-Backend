<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubKlasifikasi;
use App\Models\Klasifikasi;

class SubKlasifikasiController extends Controller
{
  // Menampilkan semua sub klasifikasi dari klasifikasi tertentu
  public function index($klasifikasiId)
  {
    $klasifikasi = Klasifikasi::find($klasifikasiId);

    if (!$klasifikasi) {
      return response()->json([
        'success' => false,
        'message' => 'Klasifikasi tidak ditemukan',
      ], 404);
    }

    $subKlasifikasis = $klasifikasi->subKlasifikasis;

    return response()->json([
      'success' => true,
      'data' => $subKlasifikasis,
    ]);
  }

  // Menambahkan sub klasifikasi baru ke dalam klasifikasi tertentu
  public function store(Request $request, $klasifikasiId)
  {
    $klasifikasi = Klasifikasi::find($klasifikasiId);

    if (!$klasifikasi) {
      return response()->json([
        'success' => false,
        'message' => 'Klasifikasi tidak ditemukan',
      ], 404);
    }

    $validated = $request->validate([
      'nama' => 'required|string|max:255',
      'sbu_code' => 'required|string|max:10',
    ]);

    $subKlasifikasi = $klasifikasi->subKlasifikasis()->create($validated);

    return response()->json([
      'success' => true,
      'message' => 'Sub Klasifikasi berhasil ditambahkan',
      'data' => $subKlasifikasi,
    ], 201);
  }

  // Menampilkan detail sub klasifikasi dari klasifikasi tertentu
  public function show($klasifikasiId, $subKlasifikasiId)
  {
    $klasifikasi = Klasifikasi::find($klasifikasiId);

    if (!$klasifikasi) {
      return response()->json([
        'success' => false,
        'message' => 'Klasifikasi tidak ditemukan',
      ], 404);
    }

    $subKlasifikasi = $klasifikasi->subKlasifikasis()->find($subKlasifikasiId);

    if (!$subKlasifikasi) {
      return response()->json([
        'success' => false,
        'message' => 'Sub Klasifikasi tidak ditemukan',
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data' => $subKlasifikasi,
    ]);
  }

  // Mengupdate sub klasifikasi dari klasifikasi tertentu
  public function update(Request $request, $klasifikasiId, $subKlasifikasiId)
  {
    $klasifikasi = Klasifikasi::find($klasifikasiId);

    if (!$klasifikasi) {
      return response()->json([
        'success' => false,
        'message' => 'Klasifikasi tidak ditemukan',
      ], 404);
    }

    $subKlasifikasi = $klasifikasi->subKlasifikasis()->find($subKlasifikasiId);

    if (!$subKlasifikasi) {
      return response()->json([
        'success' => false,
        'message' => 'Sub Klasifikasi tidak ditemukan',
      ], 404);
    }

    $validated = $request->validate([
      'nama' => 'sometimes|required|string|max:255',
      'sbu_code' => 'sometimes|required|string|max:10',
    ]);

    $subKlasifikasi->update($validated);

    return response()->json([
      'success' => true,
      'message' => 'Sub Klasifikasi berhasil diperbarui',
      'data' => $subKlasifikasi,
    ]);
  }

  // Menghapus sub klasifikasi dari klasifikasi tertentu
  public function destroy($klasifikasiId, $subKlasifikasiId)
  {
    $klasifikasi = Klasifikasi::find($klasifikasiId);

    if (!$klasifikasi) {
      return response()->json([
        'success' => false,
        'message' => 'Klasifikasi tidak ditemukan',
      ], 404);
    }

    $subKlasifikasi = $klasifikasi->subKlasifikasis()->find($subKlasifikasiId);

    if (!$subKlasifikasi) {
      return response()->json([
        'success' => false,
        'message' => 'Sub Klasifikasi tidak ditemukan',
      ], 404);
    }

    $subKlasifikasi->delete();

    return response()->json([
      'success' => true,
      'message' => 'Sub Klasifikasi berhasil dihapus',
    ]);
  }
}
