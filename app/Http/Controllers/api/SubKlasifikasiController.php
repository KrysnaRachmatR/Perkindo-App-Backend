<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubKlasifikasi;
use Illuminate\Http\Request;

class SubKlasifikasiController extends Controller
{
  public function index()
  {
    return SubKlasifikasi::with('klasifikasi')->get();
  }

  public function store(Request $request)
  {
    $request->validate([
      'klasifikasi_id' => 'required|exists:klasifikasis,id',
      'nama_sub_klasifikasi' => 'required|string|unique:sub_klasifikasis'
    ]);

    $subKlasifikasi = SubKlasifikasi::create($request->all());

    return response()->json($subKlasifikasi, 201);
  }

  public function search(Request $request)
  {
    $query = $request->input('q');  // Ambil parameter 'q' dari query string

    $results = SubKlasifikasi::where('nama_sub_klasifikasi', 'LIKE', '%' . $query . '%')->get();

    if ($results->isEmpty()) {
      return response()->json(['message' => 'Tidak ditemukan sub-klasifikasi dengan kata kunci tersebut.'], 404);
    }

    return response()->json($results, 200);
  }
  public function show($id)
  {
    return SubKlasifikasi::with('klasifikasi')->findOrFail($id);
  }

  public function update(Request $request, $id)
  {
    $request->validate([
      'nama_sub_klasifikasi' => 'required|string|unique:sub_klasifikasis,nama_sub_klasifikasi,' . $id
    ]);

    $subKlasifikasi = SubKlasifikasi::findOrFail($id);
    $subKlasifikasi->update($request->all());

    return response()->json($subKlasifikasi, 200);
  }

  public function destroy($id)
  {
    SubKlasifikasi::destroy($id);
    return response()->json(null, 204);
  }
}
