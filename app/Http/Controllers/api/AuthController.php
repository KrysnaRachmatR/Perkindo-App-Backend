<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Member;
use App\Models\Admin; // Pastikan Anda mengimpor model Admin
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register Anggota (Member).
     */
    public function register(Request $request)
    {
        $request->validate([
            'nama_perusahaan' => 'required|string',
            'nama_direktur' => 'required|string',
            'nama_penanggung_jawab' => 'required|string',
            'alamat_perusahaan' => 'required|string',
            'email' => 'required|string|email|unique:members',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $member = Member::create([
            'nama_perusahaan' => $request->nama_perusahaan,
            'nama_direktur' => $request->nama_direktur,
            'nama_penanggung_jawab' => $request->nama_penanggung_jawab,
            'alamat_perusahaan' => $request->alamat_perusahaan,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil',
            'member' => $member,
        ], 201);
    }

    /**
     * Login untuk Admin atau Anggota.
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
                'message' => 'Login as admin successful',
                'token' => $token,
                'user' => $admin,
            ], 200);
        }

        // Login sebagai Member
        $member = Member::where('email', $credentials['email'])->first();
        if ($member && Hash::check($credentials['password'], $member->password)) {
            $token = $member->createToken('Member Token', ['member:access'])->plainTextToken;

            return response()->json([
                'message' => 'Login as member successful',
                'token' => $token,
                'user' => $member,
            ], 200);
        }

        // Jika tidak cocok, kembalikan error
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete(); // Hapus semua token milik user

        return response()->json(['message' => 'Logout successful'], 200);
    }
}
