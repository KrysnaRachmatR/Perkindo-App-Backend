<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NonKonstruksiSubKlasifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NonKonstruksiSubKlasifikasiController extends Controller
{
    // Menampilkan semua sub klasifikasi
    public function index()
    {
        $subKlasifikasis = NonKonstruksiSubKlasifikasi::with('klasifikasi')->get();

        return response()->json([
            'success' => true,
            'data' => $subKlasifikasis,
        ]);
    }

    // Menyimpan data sub klasifikasi baru
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'klasifikasi_id' => 'required|exists:non_konstruksi_klasifikasis,id',
            'nama' => 'required|string|max:255',
            'sbu_code' => 'required|string|max:255',
        ]);

        try {
            $subKlasifikasi = NonKonstruksiSubKlasifikasi::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Data sub klasifikasi berhasil ditambahkan',
                'data' => $subKlasifikasi,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Menampilkan sub klasifikasi berdasarkan ID
    public function show($id)
    {
        $subKlasifikasi = NonKonstruksiSubKlasifikasi::with('klasifikasi')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $subKlasifikasi,
        ]);
    }

    // Mengupdate sub klasifikasi
    public function update(Request $request, $id)
    {
        $subKlasifikasi = NonKonstruksiSubKlasifikasi::findOrFail($id);

        $validated = $request->validate([
            'klasifikasi_id' => 'sometimes|required|exists:non_konstruksi_klasifikasis,id',
            'nama' => 'sometimes|required|string|max:255',
        ]);

        $subKlasifikasi->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data sub klasifikasi berhasil diupdate',
            'data' => $subKlasifikasi,
        ]);
    }

    // Menghapus sub klasifikasi
    public function destroy($id)
    {
        $subKlasifikasi = NonKonstruksiSubKlasifikasi::findOrFail($id);
        $subKlasifikasi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub klasifikasi berhasil dihapus',
        ]);
    }
}
