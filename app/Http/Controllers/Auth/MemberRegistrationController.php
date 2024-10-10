<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MemberRegistrationController extends Controller
{
    public function register(Request $request)
    {
        // Validasi input
        $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'nama_direktur' => 'required|string|max:255',
            'nama_penanggung_jawab' => 'required|string|max:255',
            'alamat_perusahaan' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:members',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Buat anggota baru
        $member = Member::create([
            'nama_perusahaan' => $request->nama_perusahaan,
            'nama_direktur' => $request->nama_direktur,
            'nama_penanggung_jawab' => $request->nama_penanggung_jawab,
            'alamat_perusahaan' => $request->alamat_perusahaan,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash password
        ]);

        return response()->json([
            'message' => 'Akun anggota berhasil didaftarkan',
            'member' => $member,
        ], 201);
    }
}
