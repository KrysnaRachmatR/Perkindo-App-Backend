<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SbuCode;

class SbuCodeController extends Controller
{
    // Menampilkan semua SBU Codes dengan relasi sub klasifikasi
    public function index()
    {
        $sbuCodes = SbuCode::with('subKlasifikasi')->get();

        return response()->json([
            'success' => true,
            'data' => $sbuCodes,
        ]);
    }

    // Menambahkan SBU Code baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:10|unique:sbu_codes,kode',
            'sub_klasifikasi_id' => 'required|exists:sub_klasifikasis,id'
        ]);

        $sbuCode = SbuCode::create($validated);

        return response()->json([
            'success' => true,
            'data' => $sbuCode,
        ], 201);
    }

    // Menampilkan detail SBU Code berdasarkan ID
    public function show($id)
    {
        $sbuCode = SbuCode::with('subKlasifikasi')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $sbuCode,
        ]);
    }

    // Mengupdate SBU Code berdasarkan ID
    public function update(Request $request, $id)
    {
        $sbuCode = SbuCode::findOrFail($id);

        // Validasi data yang diupdate
        $validated = $request->validate([
            'kode' => 'sometimes|required|string|max:10|unique:sbu_codes,kode,' . $sbuCode->id,
            'sub_klasifikasi_id' => 'sometimes|required|exists:sub_klasifikasis,id'
        ]);

        $sbuCode->update($validated);

        return response()->json([
            'success' => true,
            'data' => $sbuCode,
        ]);
    }

    // Menghapus SBU Code berdasarkan ID
    public function destroy($id)
    {
        SbuCode::destroy($id);

        return response()->json([
            'success' => true,
            'message' => 'Kode SBU berhasil dihapus',
        ]);
    }

    // Mencari SBU Code berdasarkan kode
    public function search(Request $request)
    {
        $query = SbuCode::query();

        if ($request->has('kode')) {
            $query->where('kode', 'like', '%' . $request->kode . '%');
        }

        $sbuCodes = $query->with('subKlasifikasi')->get();

        return response()->json([
            'success' => true,
            'data' => $sbuCodes,
        ]);
    }
}
