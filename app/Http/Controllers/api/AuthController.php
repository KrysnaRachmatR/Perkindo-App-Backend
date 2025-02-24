<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
   
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'nama_perusahaan' => 'required|string',
            'nama_direktur' => 'required|string',
            'nama_penanggung_jawab' => 'required|string',
            'alamat_perusahaan' => 'required|string',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'nama_perusahaan' => $validatedData['nama_perusahaan'],
            'nama_direktur' => $validatedData['nama_direktur'],
            'nama_penanggung_jawab' => $validatedData['nama_penanggung_jawab'],
            'alamat_perusahaan' => $validatedData['alamat_perusahaan'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string|min:8',
    ]);

    // Cek apakah email terdaftar sebagai admin
    $admin = Admin::where('email', $credentials['email'])->first();
    if ($admin && Hash::check($credentials['password'], $admin->password)) {
        // Buat token
        $token = $admin->createToken('Admin Token', ['admin:access'])->plainTextToken;
        $admin->tokens()->latest()->first()->update([
            'expires_at' => now()->addHours(6)
        ]);

        return response()->json([
            'message' => 'Login berhasil sebagai admin',
            'token' => $token,
            'expires_at' => now()->addHours(6),
            'user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'username' => $admin->username,
                'email' => $admin->email,
                'role' => 'admin'
            ],
        ], 200);
    }

    // Cek apakah email terdaftar sebagai user
    $user = User::where('email', $credentials['email'])->first();
    if ($user && Hash::check($credentials['password'], $user->password)) {
        $token = $user->createToken('User Token', ['user:access'])->plainTextToken;
        $user->tokens()->latest()->first()->update([
            'expires_at' => now()->addHours(2)
        ]);

        return response()->json([
            'message' => 'Login berhasil sebagai user',
            'token' => $token,
            'expires_at' => now()->addHours(6),
            'user' => [
                'id' => $user->id,
                'nama_perusahaan' => $user->nama_perusahaan,
                'nama_direktur' => $user->nama_direktur,
                'nama_penanggung_jawab' => $user->nama_penanggung_jawab,
                'email' => $user->email,
                'role' => 'user'
            ],
        ], 200);
    }

    return response()->json(['message' => 'Email atau password salah'], 401);
}

    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Tidak ada pengguna yang sedang login'], 401);
        }

        $user->tokens()->delete(); // Hapus semua token pengguna

        return response()->json(['message' => 'Logout berhasil'], 200);
    }
}
