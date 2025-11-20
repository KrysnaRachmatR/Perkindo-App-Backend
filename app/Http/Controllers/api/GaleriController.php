<?php

namespace App\Http\Controllers\Api;

use App\Models\Galeri;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class GaleriController extends Controller
{
    // Read galeri
    public function index()
    {
        try{
            $galeri = Galeri::all();
            return response()->json([
                'success' => true,
                'message' => 'Data retrieved successfully',
                'data' => $galeri
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Create galeri
    public function store(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'judul' => 'required|string|max:255',
                'caption' => 'required|string',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            DB::beginTransaction();

            // Simpan data awal tanpa gambar
            $galeri = Galeri::create([
                'judul' => $validated['judul'],
                'caption' => $validated['caption'],
            ]);

            // Upload gambar jika ada
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
                    'gambar' => $galeri->gambar ? Storage::url($galeri->gambar) : null,
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

            \Log::error('Galeri Store Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal pada server.',
            ], 500);
        }
    }

    // Update galeri
    public function update(Request $request, $id)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'judul'   => 'sometimes|string|max:255',
                'caption' => 'sometimes|string',
                'gambar'  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ], [
                'judul.string'   => 'Judul harus berupa teks.',
                'judul.max'      => 'Judul maksimal 255 karakter.',
                'caption.string' => 'Caption harus berupa teks.',
                'gambar.image'   => 'File harus berupa gambar.',
                'gambar.mimes'   => 'Format gambar harus jpeg, png, jpg, atau gif.',
                'gambar.max'     => 'Ukuran gambar maksimal 2MB.',
            ]);

            // Cari data galeri
            $galeri = Galeri::find($id);

            if (!$galeri) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data galeri tidak ditemukan.',
                ], 404);
            }

            DB::beginTransaction();

            // Update judul & caption jika ada
            if ($request->filled('judul')) {
                $galeri->judul = $validated['judul'];
            }
            if ($request->filled('caption')) {
                $galeri->caption = $validated['caption'];
            }

            // Jika update gambar
            if ($request->hasFile('gambar')) {

                // Hapus gambar lama jika ada
                if ($galeri->gambar && Storage::disk('public')->exists($galeri->gambar)) {
                    Storage::disk('public')->delete($galeri->gambar);
                }

                // Upload gambar baru
                $path = $request->file('gambar')->store("galeri/{$galeri->id}", 'public');
                $galeri->gambar = $path;
            }

            $galeri->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data galeri berhasil diperbarui.',
                'data' => [
                    'id' => $galeri->id,
                    'judul' => $galeri->judul,
                    'caption' => $galeri->caption,
                    'gambar' => $galeri->gambar ? Storage::url($galeri->gambar) : null,
                    'updated_at' => $galeri->updated_at,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            DB::rollBack();
            \Log::error('Galeri Update Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal pada server.',
            ], 500);
        }
    }

    // Delete galeri
    public function destroy($id)
    {
        try {
            $galeri = Galeri::findOrFail($id);

            // Tentukan folder dari model (lebih aman)
            $directory = "galeri/{$galeri->id}";

            if (Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->deleteDirectory($directory);
            }

            $galeri->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
