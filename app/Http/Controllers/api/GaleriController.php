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
        try {
            $galeri = Galeri::all();
            return response()->json([
                'message' => 'Data retrieved successfully',
                'data' => $galeri
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Create a new galeri
    public function store(Request $request)
    {
        try {
            $request->validate([
                'judul' => 'required|string|max:255',
                'caption' => 'required|string',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ], [
                'judul.required' => 'Judul harus diisi.',
                'caption.required' => 'Caption harus diisi.',
                'gambar.image' => 'File harus berupa gambar.',
                'gambar.mimes' => 'Format gambar harus jpeg, png, jpg, atau gif.',
                'gambar.max' => 'Ukuran gambar maksimal 2MB.',
            ]);

            $galeri = new Galeri();
            $galeri->judul = $request->judul;
            $galeri->caption = $request->caption;
            $galeri->save(); // Simpan dulu untuk mendapatkan ID

            if ($request->hasFile('gambar')) {
                // Direktori berdasarkan ID
                $directory = "galeri/{$galeri->id}";
                $path = $request->file('gambar')->store($directory, 'public');
                $galeri->gambar = $path;
                $galeri->save(); // Simpan ulang dengan path gambar
            }

            return response()->json([
                'message' => 'Data created successfully',
                'data' => $galeri
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update galeri
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'judul' => 'required|string|max:255',
                'caption' => 'required|string',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ], [
                'judul.required' => 'Judul harus diisi.',
                'caption.required' => 'Caption harus diisi.',
                'gambar.image' => 'File harus berupa gambar.',
                'gambar.mimes' => 'Format gambar harus jpeg, png, jpg, atau gif.',
                'gambar.max' => 'Ukuran gambar maksimal 2MB.',
            ]);

            $galeri = Galeri::findOrFail($id);
            $galeri->judul = $request->judul ?? $galeri->judul;
            $galeri->caption = $request->caption ?? $galeri->caption;

            if ($request->hasFile('gambar')) {
                // Hapus gambar lama jika ada
                if ($galeri->gambar && Storage::disk('public')->exists($galeri->gambar)) {
                    Storage::disk('public')->delete($galeri->gambar);
                }

                // Simpan gambar ke direktori berdasarkan ID
                $directory = "galeri/{$galeri->id}";
                $path = $request->file('gambar')->store($directory, 'public');
                $galeri->gambar = $path;
            }

            $galeri->save();

            return response()->json([
                'message' => 'Data updated successfully',
                'data' => [
                    'id' => $galeri->id,
                    'judul' => $galeri->judul,
                    'caption' => $galeri->caption,
                    'gambar' => $galeri->gambar ? asset('storage/' . $galeri->gambar) : null,
                    'updated_at' => $galeri->updated_at,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete galeri
    public function destroy($id)
    {
        try {
            $galeri = Galeri::findOrFail($id);

            // Hapus folder direktori terkait ID
            $directory = "galeri/{$galeri->id}";
            if (Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->deleteDirectory($directory);
            }

            $galeri->delete();

            return response()->json([
                'message' => 'Data deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
