<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Berita;
use Illuminate\Http\Request;

class BeritaController extends Controller
{
  // Menampilkan semua berita
  public function index()
{
    $beritas = Berita::all(); // ambil semua data
    return response()->json($beritas); // kembalikan sebagai JSON
}

  // Menambahkan berita baru
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
        // Cari berita berdasarkan ID
        $berita = Berita::findOrFail($id);

        // Validasi input
        $validatedData = $request->validate([
            'title'   => 'required|string|max:255',
            'caption' => 'required|string',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'title.required'   => 'Title harus diisi.',
            'caption.required' => 'Caption harus diisi.',
            'image.image'      => 'File harus berupa image.',
            'image.mimes'      => 'Format image harus jpeg, png, jpg, atau gif.',
            'image.max'        => 'Ukuran gambar maksimal 2MB.'
        ]);

        // Update field text
        $berita->title   = $validatedData['title'];
        $berita->caption = $validatedData['caption'];

        // Jika ada file image baru
        if ($request->hasFile('image')) {
            // Hapus gambar lama kalau ada
            if (!empty($berita->image) && Storage::disk('public')->exists($berita->image)) {
                Storage::disk('public')->delete($berita->image);
            }

            // Simpan gambar baru
            $path = $request->file('image')->store("berita/{$berita->id}", 'public');
            $berita->image = $path;
        }

        // Simpan perubahan
        $berita->save();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui.',
            'data'    => [
                'id'         => $berita->id,
                'title'      => $berita->title,
                'caption'    => $berita->caption,
                'image'      => $berita->image ? asset('storage/' . $berita->image) : null,
                'updated_at' => $berita->updated_at->format('Y-m-d H:i:s'),
            ]
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors'  => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal memperbarui data',
            'error'   => $e->getMessage()
        ], 500);
    }
}


  // Menampilkan berita berdasarkan ID
  public function show($id)
  {
    $berita = Berita::with('komentars')->find($id);

    if (!$berita) {
      return response()->json(['message' => 'Berita not found'], 404);
    }

    return response()->json($berita);
  }

  // Menghapus berita
  public function destroy($id)
{
    $berita = Berita::find($id);

    if (!$berita) {
        return response()->json([
            'success' => false,
            'message' => 'Berita tidak ditemukan'
        ], 404);
    }

    $berita->delete();

    return response()->json([
        'success' => true,
        'message' => 'Berita berhasil dihapus'
    ], 200);
}

}
