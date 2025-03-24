<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfileContent;
use Illuminate\Http\Request;

class ProfileController extends Controller
{

  public function getProfile()
  {
    $profileContent = ProfileContent::first();
    if (!$profileContent) {
      return response()->json([
        'success' => false,
        'message' => 'Profile content not found.',
      ], 404);
    }

    return response()->json([
      'success' => true,

      'data' => $profileContent,
    ]);
  }

  public function store(Request $request)
  {
    $request->validate([
      'header_image' => 'required|image|mimes:jpeg,png,jpg,gif',
      'title' => 'required|string|max:255',
      'section1' => 'required|string',
      'visi' => 'required|string',
      'misi' => 'required|array',
    ]);
    $imagePath = null;
    if ($request->hasFile('header_image')) {
      $imagePath = $request->file('header_image')->store('images/profile', 'public');
    }
    $profile = ProfileContent::create([
      'header_image' => $imagePath,
      'title' => $request->input('title'),
      'section1' => $request->input('section1'),
      'visi' => $request->input('visi'),
      'misi' => $request->input('misi'),
    ]);
    return response()->json([
      'success' => true,
      'message' => 'Profile content successfully created',
      'data' => $profile,
    ], 201);
  }

  public function update(Request $request, $id)
  {
    // Temukan konten profil berdasarkan ID
    $profileContent = ProfileContent::findOrFail($id);

    // Validasi data yang ada di request, hanya yang ada dalam permintaan yang akan diproses
    $data = $request->only(['title', 'section1', 'visi', 'misi']);

    // Validasi untuk setiap field yang ada
    if (isset($data['title'])) {
      $request->validate(['title' => 'string|max:255']);
    }

    if (isset($data['section1'])) {
      $request->validate(['section1' => 'string']);
    }

    if (isset($data['visi'])) {
      $request->validate(['visi' => 'string']);
    }

    if (isset($data['misi'])) {
      $request->validate(['misi' => 'array']);
    }

    // Perbarui konten profil hanya dengan data yang valid
    $profileContent->update($data);

    // Mengembalikan respons JSON dengan data yang diperbarui
    return response()->json([
      'success' => true,
      'message' => 'Profile content successfully updated',
      'data' => $profileContent,
    ]);
  }


  public function destroy($id)
  {
    $profileContent = ProfileContent::findOrFail($id);
    $profileContent->delete();

    return response()->json([
      'success' => true,
      'message' => 'Profile content successfully deleted',
    ]);
  }
}
