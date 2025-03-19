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
        $galeri = Galeri::all();
        return response()->json([
            'success' => true,
            'data' => $galeri
        ]);
    }

    // Create a new galeri
    public function store(Request $request)
    {
        try {
            $request->validate([
                'judul' => 'required|string|max:255',
                'caption' => 'required|string',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ], [
                'judul.required' => 'Judul harus diisi.',
                'caption.required' => 'Caption harus diisi.',
                'gambar.image' => 'File harus berupa gambar.',
                'gambar.mimes' => 'Format gambar harus jpeg, png, jpg, atau gif.',
                'gambar.max' => 'Ukuran gambar maksimal 2MB.',
            ]);

            $galeri = Galeri::create([
                'judul' => $request->judul,
                'caption' => $request->caption,
            ]);

            if ($request->hasFile('gambar')) {
                $path = $request->file('gambar')->store("galeri/{$galeri->id}", 'public');
                $galeri->update(['gambar' => $path]);
            }            

            return response()->json([
                'success' => true,
                'message' => 'Data created successfully',
                'data' => [
                    'id' => $galeri->id,
                    'judul' => $galeri->judul,
                    'caption' => $galeri->caption,
                    'gambar' => $galeri->gambar ? asset('storage/' . $galeri->gambar) : null,
                    'created_at' => $galeri->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                'judul' => 'sometimes|string|max:255',
                'caption' => 'sometimes|string',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $galeri = Galeri::findOrFail($id);

            if ($request->has('judul')) {
                $galeri->judul = $request->judul;
            }
            if ($request->has('caption')) {
                $galeri->caption = $request->caption;
            }

            if ($request->hasFile('gambar')) {
                // Hapus gambar lama jika ada
                if ($galeri->gambar && Storage::disk('public')->exists($galeri->gambar)) {
                    Storage::disk('public')->delete($galeri->gambar);
                }

                // Simpan gambar baru
                $path = $request->file('gambar')->store("galeri/{$galeri->id}", 'public');
                $galeri->gambar = $path;
            }

            $galeri->save();

            return response()->json([
                'success' => true,
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
                'success' => false,
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

            // Hapus direktori terkait jika ada
            $directory = "galeri/{$galeri->id}";
            if (Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->deleteDirectory($directory);
            }

            $galeri->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
