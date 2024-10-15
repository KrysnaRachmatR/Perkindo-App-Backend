<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Klasifikasi;
use App\Models\SubKlasifikasi;
use App\Models\SbuCode;
use Illuminate\Http\Request;

class SbuCodeController extends Controller
{
    public function index()
    {
        return SbuCode::with('subKlasifikasi.klasifikasi')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_klasifikasi' => 'required|string',
            'nama_sub_klasifikasi' => 'required|string',
            'kode_sbu' => 'required|string|unique:sbu_codes',
            'kbli' => 'required|string'
        ]);

        // Tambahkan atau dapatkan Klasifikasi
        $klasifikasi = Klasifikasi::firstOrCreate([
            'nama_klasifikasi' => $request->nama_klasifikasi
        ]);

        // Tambahkan atau dapatkan Sub Klasifikasi
        $subKlasifikasi = SubKlasifikasi::firstOrCreate([
            'klasifikasi_id' => $klasifikasi->id,
            'nama_sub_klasifikasi' => $request->nama_sub_klasifikasi
        ]);

        // Tambahkan Kode SBU
        $sbuCode = SbuCode::create([
            'sub_klasifikasi_id' => $subKlasifikasi->id,
            'kode_sbu' => $request->kode_sbu,
            'kbli' => $request->kbli
        ]);

        return response()->json($sbuCode, 201);
    }

    public function search(Request $request)
    {
        // Validasi input
        $request->validate([
            'query' => 'required|string|min:1', // Pastikan query tidak kosong
        ]);

        $query = $request->input('query');

        // Lakukan pencarian dengan query
        $sbuCodes = SbuCode::with('subKlasifikasi.klasifikasi') // Menyertakan relasi
            ->where('kode_sbu', 'LIKE', "%{$query}%") // Pencarian berdasarkan kode SBU
            ->orWhereHas('subKlasifikasi', function ($q) use ($query) {
                $q->where('nama_sub_klasifikasi', 'LIKE', "%{$query}%"); // Pencarian berdasarkan nama sub klasifikasi
            })
            ->get();

        // Jika data tidak ditemukan, kembalikan respons kosong
        if ($sbuCodes->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode SBU tidak ditemukan'
            ], 404);
        }

        // Kembalikan hasil jika ada
        return response()->json([
            'status' => 'success',
            'data' => $sbuCodes
        ], 200);
    }

    public function show($id)
    {
        return SbuCode::with('subKlasifikasi.klasifikasi')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kode_sbu' => 'required|string|unique:sbu_codes,kode_sbu,' . $id,
            'kbli' => 'required|string'
        ]);

        $sbuCode = SbuCode::findOrFail($id);
        $sbuCode->update($request->all());

        return response()->json($sbuCode, 200);
    }

    public function destroy($id)
    {
        SbuCode::destroy($id);
        return response()->json(null, 204);
    }
}
