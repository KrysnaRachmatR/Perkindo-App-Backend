<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RekeningTujuan;
use Illuminate\Http\Request;

class RekeningController extends Controller
{
  // Method untuk menampilkan semua rekening tujuan
  public function index()
  {
    $rekening = RekeningTujuan::all();
    return response()->json($rekening, 200);
  }

  // Method untuk menambah rekening tujuan baru
  public function store(Request $request)
  {
    $validatedData = $request->validate([
      'nama_bank' => 'required|string|max:100',
      'nomor_rekening' => 'required|string|max:50|unique:rekening_tujuan',
      'atas_nama' => 'required|string|max:100',
    ]);

    $rekening = RekeningTujuan::create([
      'nama_bank' => $validatedData['nama_bank'],
      'nomor_rekening' => $validatedData['nomor_rekening'],
      'atas_nama' => $validatedData['atas_nama'],
    ]);

    return response()->json([
      'message' => 'Rekening tujuan berhasil ditambahkan',
      'data' => $rekening
    ], 201);
  }

  // Method untuk menampilkan detail rekening tujuan berdasarkan ID
  public function show($id)
  {
    $rekening = RekeningTujuan::find($id);

    if (!$rekening) {
      return response()->json(['message' => 'Rekening tujuan tidak ditemukan'], 404);
    }

    return response()->json($rekening, 200);
  }

  // Method untuk mengupdate data rekening tujuan
  public function update(Request $request, $id)
  {
    $rekening = RekeningTujuan::find($id);

    if (!$rekening) {
      return response()->json(['message' => 'Rekening tujuan tidak ditemukan'], 404);
    }

    $validatedData = $request->validate([
      'nama_bank' => 'nullable|string|max:100',
      'nomor_rekening' => 'nullable|string|max:50' . $rekening->id,
      'atas_nama' => 'nullable|string|max:100',
    ]);

    $rekening->update($validatedData);

    return response()->json([
      'message' => 'Rekening tujuan berhasil diupdate',
      'data' => $rekening
    ], 200);
  }

  // Method untuk menghapus rekening tujuan
  public function destroy($id)
  {
    $rekening = RekeningTujuan::find($id);

    if (!$rekening) {
      return response()->json(['message' => 'Rekening tujuan tidak ditemukan'], 404);
    }

    $rekening->delete();

    return response()->json(['message' => 'Rekening tujuan berhasil dihapus'], 200);
  }
}
