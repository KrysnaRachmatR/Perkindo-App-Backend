<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\SbuKonstruksi;
use Illuminate\Http\Request;

class SbuKonstruksiController extends Controller
{
    public function index()
    {
        return SbuKonstruksi::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'no' => 'required|integer|unique:sbu_konstruksi,no', // Memperbaiki kesalahan ketik
            'nama_badan_usaha' => 'required',
            'alamat' => 'required',
            'direktur' => 'required',
            'kode_sbu' => 'required',
            'tanggal_masa_berlaku' => 'required|date',
            'sampai_dengan' => 'required|date',
        ]);

        // Buat entri baru
        $sbuKonstruksi = SbuKonstruksi::create($request->all());

        return response()->json($sbuKonstruksi, 201);
    }

    public function show($id)
    {
        return SbuKonstruksi::find($id);
    }

    public function update(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'nama_badan_usaha' => 'required',
            'alamat' => 'required',
            'direktur' => 'required',
            'kode_sbu' => 'required',
            'tanggal_masa_berlaku' => 'required|date',
            'sampai_dengan' => 'required|date',
        ]);

        // Cari data berdasarkan ID
        $sbuKonstruksi = SbuKonstruksi::find($id);

        if (!$sbuKonstruksi) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Update data tanpa mengubah kolom 'no'
        $sbuKonstruksi->update($request->only([
            'nama_badan_usaha',
            'alamat',
            'direktur',
            'kode_sbu',
            'tanggal_masa_berlaku',
            'sampai_dengan'
        ]));

        return response()->json($sbuKonstruksi);
    }


    public function destroy(string $id)
    {
        $sbuKonstruksi = SbuKonstruksi::find($id);

        if (!$sbuKonstruksi) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $sbuKonstruksi->delete();

        return response()->json(['message' => 'Data berhasil dihapus']);
    }

    public function count()
    {
        $count = SbuKonstruksi::count();
        return response()->json(['count' => $count]);
    }
    public function indexPublic()
    {
        $data = SbuKonstruksi::select('nama_badan_usaha', 'direktur', 'alamat', 'tanggal_masa_berlaku', 'sampai_dengan', 'kode_sbu')->get();
        return response()->json($data);
    }
}
