<?php

namespace App\Http\Controllers\Api;

use App\Models\Anggota;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AnggotaController extends Controller
{
  public function index(Request $request)
  {
    // Filter berdasarkan pencarian
    $query = Anggota::query();

    if ($request->has('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('nama_badan_usaha', 'LIKE', "%{$search}%")
          ->orWhere('kode_sbu', 'LIKE', "%{$search}%")
          ->orWhere('alamat', 'LIKE', "%{$search}%")
          ->orWhere('direktur', 'LIKE', "%{$search}%");
      });
    }

    $anggota = $query->get();
    return response()->json($anggota);
  }

  public function store(Request $request)
  {
    $validatedData = $request->validate([
      'nama_badan_usaha' => 'required|string|max:255',
      'alamat' => 'required|string',
      'direktur' => 'required|string',
      'kode_sbu' => 'required|string',
      'tanggal_masa_berlaku' => 'required|date',
      'sampai_dengan' => 'required|date',
      'jenis_sbu' => 'required|in:konstruksi,non-konstruksi',
    ]);

    $anggota = Anggota::create($validatedData);
    $anggota->checkStatusAktif();

    return response()->json([
      'message' => 'Anggota created successfully',
      'data' => $anggota,
    ], 201);
  }

  public function show($id)
  {
    $anggota = Anggota::findOrFail($id); // Menggunakan findOrFail untuk 404 otomatis
    return response()->json($anggota);
  }

  public function update(Request $request, $id)
  {
    $anggota = Anggota::findOrFail($id);

    $validatedData = $request->validate([
      'nama_badan_usaha' => 'required|string|max:255',
      'alamat' => 'required|string',
      'direktur' => 'required|string',
      'kode_sbu' => 'required|string',
      'tanggal_masa_berlaku' => 'required|date',
      'sampai_dengan' => 'required|date',
      'jenis_sbu' => 'required|in:konstruksi,non-konstruksi',
    ]);

    $anggota->update($validatedData);
    $anggota->checkStatusAktif(); // Cek status aktif setelah pembaruan

    return response()->json([
      'message' => 'Anggota updated successfully',
      'data' => $anggota,
    ], 200);
  }

  public function destroy($id)
  {
    $anggota = Anggota::findOrFail($id);
    $anggota->delete();

    return response()->json([
      'message' => 'Anggota deleted successfully',
    ], 200);
  }
}
