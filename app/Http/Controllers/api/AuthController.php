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
    /**
     * Register User (Anggota).
     */
    public function register(Request $request)
    {
        $request->validate([
            'nama_perusahaan' => 'required|string',
            'nama_direktur' => 'required|string',
            'nama_penanggung_jawab' => 'required|string',
            'alamat_perusahaan' => 'required|string',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'nama_perusahaan' => $request->nama_perusahaan,
            'nama_direktur' => $request->nama_direktur,
            'nama_penanggung_jawab' => $request->nama_penanggung_jawab,
            'alamat_perusahaan' => $request->alamat_perusahaan,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil',
            'user' => $user,
        ], 201);
    }

    /**
     * Login untuk Admin atau User.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:8',
        ]);

        // Login sebagai Admin
        $admin = Admin::where('email', $credentials['email'])->first();
        if ($admin && Hash::check($credentials['password'], $admin->password)) {
            $token = $admin->createToken('Admin Token', ['admin:access'])->plainTextToken;

            return response()->json([
                'message' => 'Login sebagai admin berhasil',
                'token' => $token,
                'user' => $admin,
            ], 200);
        }

        // Login sebagai User
        $user = User::where('email', $credentials['email'])->first();
        if ($user && Hash::check($credentials['password'], $user->password)) {
            $token = $user->createToken('User Token', ['user:access'])->plainTextToken;

            return response()->json([
                'message' => 'Login sebagai user berhasil',
                'token' => $token,
                'user' => $user,
            ], 200);
        }

        // Jika tidak cocok, kembalikan error
        return response()->json(['message' => 'Email atau password salah'], 401);
    }

    /**
     * Logout dari sistem.
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete(); // Hapus semua token milik user atau admin

        return response()->json(['message' => 'Logout berhasil'], 200);
    }
}
