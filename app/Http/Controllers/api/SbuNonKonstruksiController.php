<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\SbuNonKonstruksi;
use Illuminate\Http\Request;

class SbuNonKonstruksiController extends Controller
{
    public function index()
    {
        return SbuNonKonstruksi::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'no' => 'required|integer|unique:sbu_non_konstruksi,no', // Memperbaiki kesalahan ketik
            'nama_badan_usaha' => 'required',
            'alamat' => 'required',
            'direktur' => 'required',
            'kode_sbu' => 'required',
            'tanggal_masa_berlaku' => 'required|date',
            'sampai_dengan' => 'required|date',
        ]);

        // Buat entri baru
        $sbuNonKonstruksi = SbuNonKonstruksi::create($request->all());

        return response()->json($sbuNonKonstruksi, 201);
    }

    public function show($id)
    {
        $sbuNonKonstruksi = SbuNonKonstruksi::find($id);

        if (!$sbuNonKonstruksi) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($sbuNonKonstruksi);
    }

    public function update(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'no' => 'required|integer|unique:sbu_non_konstruksi,no,' . $id, // Pastikan no unik, kecuali untuk ID ini
            'nama_badan_usaha' => 'required',
            'alamat' => 'required',
            'direktur' => 'required',
            'kode_sbu' => 'required',
            'tanggal_masa_berlaku' => 'required|date',
            'sampai_dengan' => 'required|date',
        ]);

        // Cari data berdasarkan ID
        $sbuNonKonstruksi = SbuNonKonstruksi::find($id);

        if (!$sbuNonKonstruksi) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Update data tanpa mengubah kolom 'no'
        $sbuNonKonstruksi->update($request->only([
            'nama_badan_usaha',
            'alamat',
            'direktur',
            'kode_sbu',
            'tanggal_masa_berlaku',
            'sampai_dengan'
        ]));

        return response()->json($sbuNonKonstruksi);
    }

    public function destroy($id)
    {
        $sbuNonKonstruksi = SbuNonKonstruksi::find($id);

        if (!$sbuNonKonstruksi) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $sbuNonKonstruksi->delete();

        return response()->json(['message' => 'Data berhasil dihapus']);
    }
    public function count()
    {
        $count = SbuNonKonstruksi::count();
        return response()->json(['count' => $count]);
    }
}
