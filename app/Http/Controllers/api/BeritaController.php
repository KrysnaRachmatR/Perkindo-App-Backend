<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Berita;
use Illuminate\Http\Request;

class BeritaController extends Controller
{

    public function index()
    {
        try{
            $berita = Berita::all();
            return response()->json([
                'success' => true,
                'message' => 'Data retrieved successfully',
                'data' => $berita
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'caption' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ], [
                'title.required' => 'Title harus diisi.',
                'caption.required' => 'Caption harus diisi.',
                'image.image' => 'File harus berupa image.',
                'image.mimes' => 'Format image harus jpeg, png, jpg, atau gif.'
            ]);
    
            // Simpan berita tanpa image terlebih dahulu
            $berita = Berita::create([
                'title' => $validatedData['title'],
                'caption' => $validatedData['caption'],
                'image' => null, // Pastikan kolom image memiliki nilai default
            ]);
    
            // Jika ada file image, simpan dan update berita
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store("berita/{$berita->id}", 'public');
                $berita->update(['image' => $path]);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Data created successfully',
                'data' => [
                    'id' => $berita->id,
                    'title' => $berita->title,
                    'caption' => $berita->caption,
                    'image' => $berita->image ? asset('storage/' . $berita->image) : null,
                    'created_at' => $berita->created_at,
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

    public function update(Request $request, $id)
    {
        try {
            // Ambil data berita
            $berita = Berita::findOrFail($id);

            // Validasi input
            $rules = [
                'title'   => 'required|string|max:255',
                'caption' => 'required|string',
                'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ];

            $messages = [
                'title.required'   => 'Title harus diisi.',
                'caption.required' => 'Caption harus diisi.',
                'image.image'      => 'File harus berupa image.',
                'image.mimes'      => 'Format image harus jpeg, png, jpg, atau gif.',
                'image.max'        => 'Ukuran gambar maksimal 2MB.'
            ];

            $validated = $request->validate($rules, $messages);

            // Update field text
            $berita->fill([
                'title'   => $validated['title'],
                'caption' => $validated['caption'],
            ]);

            // Jika upload image baru
            if ($request->hasFile('image')) {

                // Hapus file lama jika ada
                if ($berita->image && Storage::disk('public')->exists($berita->image)) {
                    Storage::disk('public')->delete($berita->image);
                }

                // Simpan file baru
                $path = $request->file('image')->store("berita/{$berita->id}", 'public');
                $berita->image = $path;
            }

            // Commit update
            $berita->save();

            return response()->json([
                'status'  => true,
                'message' => 'Data berhasil diperbarui.',
                'data' => [
                    'id'         => $berita->id,
                    'title'      => $berita->title,
                    'caption'    => $berita->caption,
                    'image'      => $berita->image ? asset("storage/{$berita->image}") : null,
                    'updated_at' => $berita->updated_at->format('Y-m-d H:i:s'),
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui data.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $berita = Berita::findOrFail($id);
            $berita->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Berita berhasil dihapus'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Berita tidak ditemukan'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Terjadi kesalahan pada server'
            ], 500);
        }
    }
}
