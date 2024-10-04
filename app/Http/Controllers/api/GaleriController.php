<?php

namespace App\Http\Controllers\Api;

use App\Models\Galeri;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class GaleriController extends Controller
{
    // Get all galeri
    public function index()
    {
        return Galeri::all();
    }

    // Create a new galeri
    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string',
            'caption' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $galeri = new Galeri();
        $galeri->judul = $request->judul;
        $galeri->caption = $request->caption;

        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('galeri', 'public');
            $galeri->gambar = $path;
        }

        $galeri->save();
        return response()->json($galeri, 201);
    }

    // Update a galeri
    public function update(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'judul' => 'required|string|max:255',
            'caption' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Anda dapat menyesuaikan ukuran dan format sesuai kebutuhan
        ]);

        $galeri = Galeri::findOrFail($id);

        // Update data
        $galeri->judul = $request->judul;
        $galeri->caption = $request->caption;

        // Cek apakah ada gambar yang di-upload
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($galeri->gambar) {
                Storage::delete($galeri->gambar);
            }

            // Simpan gambar baru
            $galeri->gambar = $request->file('gambar')->store('galeri', 'public'); // Simpan di folder 'storage/app/public/galeri'
        }

        $galeri->save(); // Simpan perubahan

        return response()->json([
            'message' => 'Data updated successfully',
            'data' => $galeri,
        ]);
    }

    // Delete a galeri
    public function destroy($id)
    {
        $galeri = Galeri::findOrFail($id);
        if ($galeri->gambar) {
            Storage::disk('public')->delete($galeri->gambar);
        }
        $galeri->delete();
        return response()->json(null, 204);
    }
}
