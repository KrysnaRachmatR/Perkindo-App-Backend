<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kbli;

class KbliController extends Controller
{
  // Menampilkan semua KBLI dengan relasi sub klasifikasi
  public function index()
  {
    $kblis = Kbli::with('subKlasifikasi')->get();

    return response()->json([
      'success' => true,
      'data' => $kblis,
    ]);
  }

  // Menambahkan KBLI baru
  public function store(Request $request)
  {
    $validated = $request->validate([
      'kode' => 'required|string|max:10|unique:kblis,kode',
      'sub_klasifikasi_id' => 'required|exists:sub_klasifikasis,id',
    ]);

    $kbli = Kbli::create($validated);

    return response()->json([
      'success' => true,
      'data' => $kbli,
    ], 201);
  }

  // Menampilkan detail KBLI berdasarkan ID
  public function show($id)
  {
    $kbli = Kbli::with('subKlasifikasi')->findOrFail($id);

    return response()->json([
      'success' => true,
      'data' => $kbli,
    ]);
  }

  // Mengupdate KBLI berdasarkan ID
  public function update(Request $request, $id)
  {
    $kbli = Kbli::findOrFail($id);

    // Validasi data yang diupdate
    $validated = $request->validate([
      'kode' => 'sometimes|required|string|max:10|unique:kblis,kode,' . $kbli->id,
      'sub_klasifikasi_id' => 'sometimes|required|exists:sub_klasifikasis,id',
    ]);

    $kbli->update($validated);

    return response()->json([
      'success' => true,
      'data' => $kbli,
    ]);
  }

  // Menghapus KBLI berdasarkan ID
  public function destroy($id)
  {
    Kbli::destroy($id);

    return response()->json([
      'success' => true,
      'message' => 'KBLI berhasil dihapus',
    ]);
  }
}
