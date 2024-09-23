<?php

namespace App\Http\Controllers\Api;

use App\Models\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validasi input
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        // Mencari admin berdasarkan username
        $admin = Admin::where('username', $credentials['username'])->first();

        // Cek apakah admin ditemukan dan password valid
        if ($admin && password_verify($credentials['password'], $admin->password)) {
            // Buat token
            $token = $admin->createToken('API Token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => $admin,
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }


    public function logout(Request $request)
    {
        // Mengambil admin yang sedang login
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            // Hapus semua token yang dimiliki admin
            $admin->tokens()->delete();

            return response()->json(['message' => 'Logout successful'], 200);
        }

        return response()->json(['message' => 'User not authenticated'], 401);
    }
}
