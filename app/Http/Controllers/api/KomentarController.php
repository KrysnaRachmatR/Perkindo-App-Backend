<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Komentar;
use Illuminate\Http\Request;

class KomentarController extends Controller
{
  public function store(Request $request, $berita_id)
  {
    // Validasi input
    $validatedData = $request->validate([
      'name' => 'required|string|max:255',
      'comment' => 'required|string',
    ]);

    // Menyimpan komentar baru
    $komentar = Komentar::create([
      'berita_id' => $berita_id, // Menggunakan berita_id dari URL
      'name' => $validatedData['name'],
      'comment' => $validatedData['comment'],
    ]);

    // Mengembalikan respons sukses
    return response()->json($komentar, 201);
  }

  /**
   * Mengambil komentar berdasarkan berita_id.
   *
   * @param  int  $berita_id
   * @return \Illuminate\Http\JsonResponse
   */
  public function index($berita_id)
  {
    // Mengambil komentar yang terkait dengan berita
    $komentars = Komentar::where('berita_id', $berita_id)->get();

    // Mengembalikan daftar komentar
    return response()->json($komentars);
  }
}
