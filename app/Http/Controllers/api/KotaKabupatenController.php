<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KotaKabupaten;
use Illuminate\Http\Request;

class KotaKabupatenController extends Controller
{
  public function index()
  {
    $kotaKabupaten = KotaKabupaten::all();

    return response()->json($kotaKabupaten);
  }

  public function store(Request $request)
  {
    $validatedData = $request->validate([
      'nama' => 'required|string|max:100',
    ]);

    $kotaKabupaten = KotaKabupaten::create([
      'nama' => $validatedData['nama'],
    ]);

    return response()->json([
      'message' => 'Kota/Kabupaten berhasil ditambahkan',
      'data' => $kotaKabupaten
    ], 201);
  }

  public function show($id)
  {
    $kotaKabupaten = KotaKabupaten::find($id);

    if (!$kotaKabupaten) {
      return response()->json(['message' => 'Kota/Kabupaten tidak ditemukan'], 404);
    }

    return response()->json($kotaKabupaten, 200);
  }

  public function update(Request $request, $id)
  {
    $kotaKabupaten = KotaKabupaten::find($id);

    if (!$kotaKabupaten) {
      return response()->json(['message' => 'Kota/Kabupaten tidak ditemukan'], 404);
    }

    $validatedData = $request->validate([
      'nama' => 'nullable|string|max:100'
    ]);

    $kotaKabupaten->update($validatedData);

    return response()->json([
      'message' => 'Kota/Kabupaten berhasil diupdate',
      'data' => $kotaKabupaten
    ], 200);
  }

  public function destroy($id)
  {
    $kotaKabupaten = KotaKabupaten::find($id);

    if (!$kotaKabupaten) {
      return response()->json(['message' => 'Kota/Kabupaten tidak ditemukan'], 404);
    }

    $kotaKabupaten->delete();

    return response()->json(['message' => 'Kota/Kabupaten berhasil dihapus'], 200);
  }
}
