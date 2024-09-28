<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Galeri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GaleriController extends Controller
{
    // Get all galleries
    public function index()
    {
        $galeri = Galeri::all();
        return response()->json($galeri, 200);
    }

    // Get single gallery item
    public function show($id)
    {
        $galeri = Galeri::find($id);

        if (!$galeri) {
            return response()->json(['message' => 'Gallery not found'], 404);
        }

        return response()->json($galeri, 200);
    }

    // Create new gallery item
    public function store(Request $request)
    {
        $request->validate([
            'gambar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'judul' => 'required|string|max:255',
            'caption' => 'required|string',
        ]);

        // Store the image
        $imagePath = $request->file('gambar')->store('galeri', 'public');

        // Create the gallery record
        $galeri = Galeri::create([
            'gambar' => $imagePath,
            'judul' => $request->judul,
            'caption' => $request->caption,
        ]);

        return response()->json($galeri, 201);
    }

    // Update gallery item
    public function update(Request $request, $id)
    {
        $galeri = Galeri::find($id);

        if (!$galeri) {
            return response()->json(['message' => 'Gallery not found'], 404);
        }

        $request->validate([
            'gambar' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'judul' => 'string|max:255',
            'caption' => 'string',
        ]);

        // Update image if provided
        if ($request->hasFile('gambar')) {
            // Delete old image
            Storage::disk('public')->delete($galeri->gambar);

            // Store new image
            $imagePath = $request->file('gambar')->store('galeri', 'public');
            $galeri->gambar = $imagePath;
        }

        // Update title and caption
        $galeri->judul = $request->judul ?? $galeri->judul;
        $galeri->caption = $request->caption ?? $galeri->caption;
        $galeri->save();

        return response()->json($galeri, 200);
    }

    // Delete gallery item
    public function destroy($id)
    {
        $galeri = Galeri::find($id);

        if (!$galeri) {
            return response()->json(['message' => 'Gallery not found'], 404);
        }

        // Delete image file
        Storage::disk('public')->delete($galeri->gambar);

        // Delete gallery record
        $galeri->delete();

        return response()->json(['message' => 'Gallery deleted successfully'], 200);
    }
}
