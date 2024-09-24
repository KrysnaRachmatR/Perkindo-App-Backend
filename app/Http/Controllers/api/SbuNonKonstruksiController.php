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
            'nama_badan_usaha' => 'required',
            'alamat' => 'required',
            'direktur' => 'required',
            'kode_sbu' => 'required',
            'tanggal_masa_berlaku' => 'required|date',
            'sampai_dengan' => 'required|date',
            'no' => 'required|interger',
        ]);

        $sbuKonstruksi = SbuNonKonstruksi::create($request->all());

        return response()->json($sbuKonstruksi, 201);
    }

    public function show($id)
    {
        return SbuNonKonstruksi::find($id);
    }

    public function update(Request $request, $id)
    {
        $sbuNonKonstruksi = SbuNonKonstruksi::find($id);

        if (!$sbuNonKonstruksi) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $sbuNonKonstruksi->update($request->all());

        return response()->json($sbuNonKonstruksi);
    }

    public function destroy(string $id)
    {
        $sbuKonstruksi = SbuNonKonstruksi::find($id);

        if (!$sbuKonstruksi) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $sbuKonstruksi->delete();

        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
