<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;
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

      // Ubah path gambar jadi URL lengkap agar bisa dipakai di frontend
      $profileContent->header_image = $profileContent->header_image
        ? asset(Storage::url($profileContent->header_image))
        : null;

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
    $profileContent = ProfileContent::findOrFail($id);

    $validatedData = $request->validate([
        'title'        => 'sometimes|string|max:255',
        'section1'     => 'sometimes|string',
        'visi'         => 'sometimes|string',
        'misi'         => 'sometimes|array',
        'header_image' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    if ($request->has('misi')) {
        $validatedData['misi'] = $request->misi;
    }

    if ($request->hasFile('header_image')) {
        $path = $request->file('header_image')->store('images/profile', 'public');
        $validatedData['header_image'] = $path;
    }

    $profileContent->fill($validatedData);
    $profileContent->save();

    return response()->json([
        'success' => true,
        'message' => 'Profile content successfully updated',
        'data'    => $profileContent->fresh(),
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
