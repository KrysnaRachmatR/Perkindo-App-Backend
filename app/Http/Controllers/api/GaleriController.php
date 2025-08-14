<?php

namespace App\Http\Controllers\Api;

use App\Models\Galeri;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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
            // Validasi input
            $validated = $request->validate([
                'judul' => 'required|string|max:255',
                'caption' => 'required|string',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ]);

            DB::beginTransaction();

            // Simpan data awal tanpa gambar
            $galeri = Galeri::create([
                'judul' => $validated['judul'],
                'caption' => $validated['caption'],
            ]);

            // Jika ada gambar, simpan ke storage
            if ($request->hasFile('gambar')) {
                $path = $request->file('gambar')->store("galeri/{$galeri->id}", 'public');
                $galeri->update(['gambar' => $path]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data galeri berhasil disimpan.',
                'data' => [
                    'id' => $galeri->id,
                    'judul' => $galeri->judul,
                    'caption' => $galeri->caption,
                    'gambar' => $galeri->gambar ? asset('storage/' . $galeri->gambar) : null,
                    'created_at' => $galeri->created_at,
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage(),
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
