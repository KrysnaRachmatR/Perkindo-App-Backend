<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KlasifikasiNonKonstruksi;

class NonKonstruksiKlasifikasiController extends Controller
{
    // Menampilkan semua klasifikasi dengan sub klasifikasi
    public function indexWithSubKlasifikasiAndCodes()
    {
        $klasifikasis = KlasifikasiNonKonstruksi::with('subKlasifikasis')->get();

        return response()->json([
            'success' => true,
            'data' => $klasifikasis,
        ]);
    }

    // Menyimpan klasifikasi baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        $klasifikasi = KlasifikasiNonKonstruksi::create($validated);

        return response()->json([
            'success' => true,
            'data' => $klasifikasi,
        ], 201);
    }

    // Menampilkan klasifikasi berdasarkan ID
    public function show($id)
    {
        $klasifikasi = KlasifikasiNonKonstruksi::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $klasifikasi,
        ]);
    }

    // Mengupdate klasifikasi
    public function update(Request $request, $id)
    {
        $klasifikasi = KlasifikasiNonKonstruksi::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
        ]);

        $klasifikasi->update($validated);

        return response()->json([
            'success' => true,
            'data' => $klasifikasi,
        ]);
    }

    // Menghapus klasifikasi
    public function destroy($id)
    {
        $klasifikasi = KlasifikasiNonKonstruksi::findOrFail($id);
        $klasifikasi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Klasifikasi berhasil dihapus',
        ]);
    }
}
