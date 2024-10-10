<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MemberAuthController extends Controller
{
  public function login(Request $request)
  {
    // Validasi input
    $credentials = $request->validate([
      'email' => 'required|string|email',
      'password' => 'required|string|min:8',
    ]);

    // Cek kredensial anggota
    $member = Member::where('email', $credentials['email'])->first();

    if ($member && Hash::check($credentials['password'], $member->password)) {
      // Buat token
      $token = $member->createToken('API Token')->plainTextToken;

      return response()->json([
        'message' => 'Login successful',
        'token' => $token,
        'user' => $member,
      ], 200);
    }

    return response()->json([
      'message' => 'Invalid credentials'
    ], 401);
  }
}
