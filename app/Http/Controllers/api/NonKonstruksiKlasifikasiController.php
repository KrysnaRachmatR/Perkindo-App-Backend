<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NonKonstruksiKlasifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NonKonstruksiKlasifikasiController extends Controller
{
    // Menampilkan semua klasifikasi dengan sub klasifikasi
    public function index()
    {
        $klasifikasis = NonKonstruksiKlasifikasi::all();

        return response()->json([
            'success' => true,
            'data' => $klasifikasis,
        ]);
    }

    // Menyimpan data klasifikasi baru
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        try {
            $klasifikasi = NonKonstruksiKlasifikasi::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Data klasifikasi berhasil ditambahkan',
                'data' => $klasifikasi,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Menampilkan data klasifikasi berdasarkan ID
    public function show($id)
    {
        $klasifikasi = NonKonstruksiKlasifikasi::with('subKlasifikasis')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $klasifikasi,
        ]);
    }

    // Mengupdate data klasifikasi
    public function update(Request $request, $id)
    {
        $klasifikasi = NonKonstruksiKlasifikasi::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
        ]);

        $klasifikasi->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data klasifikasi berhasil diupdate',
            'data' => $klasifikasi,
        ]);
    }

    // Menghapus data klasifikasi
    public function destroy($id)
    {
        $klasifikasi = NonKonstruksiKlasifikasi::findOrFail($id);
        $klasifikasi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Klasifikasi berhasil dihapus',
        ]);
    }
}
