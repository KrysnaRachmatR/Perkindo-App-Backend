<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubKlasifikasiNonKonstruksi;

class NonKonstruksiSubKlasifikasiController extends Controller
{
    // Menampilkan semua sub klasifikasi berdasarkan ID klasifikasi
    public function index($klasifikasiId)
    {
        $subKlasifikasis = SubKlasifikasiNonKonstruksi::where('klasifikasi_id', $klasifikasiId)->get();

        return response()->json([
            'success' => true,
            'data' => $subKlasifikasis,
        ]);
    }

    // Menyimpan sub klasifikasi baru
    public function store(Request $request, $klasifikasiId)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'sbu_code' => 'required|string|max:10',
        ]);

        $validated['klasifikasi_id'] = $klasifikasiId;

        $subKlasifikasi = SubKlasifikasiNonKonstruksi::create($validated);

        return response()->json([
            'success' => true,
            'data' => $subKlasifikasi,
        ], 201);
    }

    // Menampilkan sub klasifikasi berdasarkan ID
    public function show($klasifikasiId, $subKlasifikasiId)
    {
        $subKlasifikasi = SubKlasifikasiNonKonstruksi::where('klasifikasi_id', $klasifikasiId)
            ->findOrFail($subKlasifikasiId);

        return response()->json([
            'success' => true,
            'data' => $subKlasifikasi,
        ]);
    }

    // Mengupdate sub klasifikasi
    public function update(Request $request, $klasifikasiId, $subKlasifikasiId)
    {
        $subKlasifikasi = SubKlasifikasiNonKonstruksi::where('klasifikasi_id', $klasifikasiId)
            ->findOrFail($subKlasifikasiId);

        $validated = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'sbu_code' => 'sometimes|required|string|max:10',
        ]);

        $subKlasifikasi->update($validated);

        return response()->json([
            'success' => true,
            'data' => $subKlasifikasi,
        ]);
    }

    // Menghapus sub klasifikasi
    public function destroy($klasifikasiId, $subKlasifikasiId)
    {
        $subKlasifikasi = SubKlasifikasiNonKonstruksi::where('klasifikasi_id', $klasifikasiId)
            ->findOrFail($subKlasifikasiId);

        $subKlasifikasi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub Klasifikasi berhasil dihapus',
        ]);
    }
}
