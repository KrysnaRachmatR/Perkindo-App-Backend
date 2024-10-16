<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubKlasifikasi;

class SubKlasifikasiController extends Controller
{
  public function index($klasifikasiId)
  {
    $subKlasifikasis = SubKlasifikasi::where('klasifikasi_id', $klasifikasiId)->get();

    return response()->json([
      'success' => true,
      'data' => $subKlasifikasis,
    ]);
  }

  public function store(Request $request, $klasifikasiId)
  {
    $validated = $request->validate([
      'nama' => 'required|string|max:255',
      'sbu_code' => 'required|string|max:10',
    ]);

    $validated['klasifikasi_id'] = $klasifikasiId;
    $subKlasifikasi = SubKlasifikasi::create($validated);
    return response()->json([
      'success' => true,
      'data' => $subKlasifikasi,
      'message' => 'Sub Klasifikasi berhasil ditambahkan.',
    ], 201);
  }

  public function show($klasifikasiId, $subKlasifikasiId)
  {
    $subKlasifikasi = SubKlasifikasi::where('klasifikasi_id', $klasifikasiId)
      ->findOrFail($subKlasifikasiId);

    return response()->json([
      'success' => true,
      'data' => $subKlasifikasi,
    ]);
  }

  public function update(Request $request, $klasifikasiId, $subKlasifikasiId)
  {
    $subKlasifikasi = SubKlasifikasi::where('klasifikasi_id', $klasifikasiId)
      ->findOrFail($subKlasifikasiId);

    $validated = $request->validate([
      'nama' => 'sometimes|required|string|max:255',
      'sbu_code' => 'sometimes|required|string|max:10',
    ]);

    $subKlasifikasi->update($validated);

    return response()->json([
      'success' => true,
      'data' => $subKlasifikasi,
      'message' => 'Sub Klasifikasi berhasil diperbarui.',
    ]);
  }

  public function destroy($klasifikasiId, $subKlasifikasiId)
  {
    $subKlasifikasi = SubKlasifikasi::where('klasifikasi_id', $klasifikasiId)
      ->findOrFail($subKlasifikasiId);

    $subKlasifikasi->delete();

    return response()->json([
      'success' => true,
      'message' => 'Sub Klasifikasi berhasil dihapus.',
    ]);
  }
}
