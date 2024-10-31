<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use Illuminate\Http\Request;

class BeritaController extends Controller
{
  // Menampilkan semua berita
  public function index()
  {
    $beritas = Berita::with('komentars')->get();
    return response()->json($beritas);
  }

  // Menambahkan berita baru
  public function store(Request $request)
  {
    $request->validate([
      'title' => 'required|string|max:255',
      'caption' => 'nullable|string',
      'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Validasi file gambar
    ]);

    // Simpan gambar ke direktori 'public/images/berita'
    $imagePath = $request->file('image')->store('images/berita', 'public');

    // Simpan data berita ke database
    $berita = new Berita();
    $berita->title = $request->input('title');
    $berita->caption = $request->input('caption');
    $berita->image = $imagePath; // Simpan path gambar
    $berita->save();

    return response()->json(['message' => 'Berita berhasil ditambahkan'], 201);
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
      return response()->json(['message' => 'Berita not found'], 404);
    }

    $berita->delete();

    return response()->json(['message' => 'Berita deleted successfully']);
  }
}
