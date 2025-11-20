<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\ProfileContent;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        $profileContent = ProfileContent::first();

        if (!$profileContent) {
            return response()->json([
                'success' => false,
                'message' => 'Profile content not found.',
            ], 404);
        }

        // Generate URL gambar hanya untuk response, tanpa mengubah model mentah
        $data = $profileContent->toArray();
        $data['header_image'] = $profileContent->header_image
            ? asset(Storage::url($profileContent->header_image))
            : null;

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'header_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'title'        => 'required|string|max:255',
                'section1'     => 'required|string',
                'visi'         => 'required|string',
                'misi'         => 'required|array',
            ]);

            DB::beginTransaction();

            // Upload gambar
            $imagePath = null;
            if ($request->hasFile('header_image')) {
                $imagePath = $request->file('header_image')
                    ->store('images/profile', 'public');
            }

            // Create data
            $profile = ProfileContent::create([
                'header_image' => $imagePath,
                'title'        => $validated['title'],
                'section1'     => $validated['section1'],
                'visi'         => $validated['visi'],
                'misi'         => $validated['misi'],
            ]);

            DB::commit();

            // Format output URL
            $profile->header_image = $imagePath ? asset('storage/' . $imagePath) : null;

            return response()->json([
                'success' => true,
                'message' => 'Profile content successfully created.',
                'data'    => $profile,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create profile content.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validasi
            $validated = $request->validate([
                'title'        => 'sometimes|string|max:255',
                'section1'     => 'sometimes|string',
                'visi'         => 'sometimes|string',
                'misi'         => 'sometimes|array',
                'header_image' => 'sometimes|image|mimes:jpg,jpeg,png,gif|max:2048',
            ]);

            DB::beginTransaction();

            // Ambil data
            $profile = ProfileContent::findOrFail($id);

            // Jika ada file baru, hapus file lama dan upload baru
            if ($request->hasFile('header_image')) {

                // Hapus gambar lama
                if ($profile->header_image && Storage::disk('public')->exists($profile->header_image)) {
                    Storage::disk('public')->delete($profile->header_image);
                }

                // Simpan gambar baru
                $path = $request->file('header_image')->store('images/profile', 'public');
                $validated['header_image'] = $path;
            }

            // Update data
            $profile->fill($validated);
            $profile->save();

            DB::commit();

            // Format URL gambar
            $profile->header_image = $profile->header_image
                ? asset('storage/' . $profile->header_image)
                : null;

            return response()->json([
                'success' => true,
                'message' => 'Profile content successfully updated.',
                'data'    => $profile,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile content.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Ambil data
            $profile = ProfileContent::findOrFail($id);

            // Hapus gambar jika ada
            if ($profile->header_image && Storage::disk('public')->exists($profile->header_image)) {
                Storage::disk('public')->delete($profile->header_image);
            }

            // Hapus row
            $profile->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile content successfully deleted.',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile content not found.',
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete profile content.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

}
