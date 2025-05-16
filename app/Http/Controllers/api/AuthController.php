<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Admin;
// use App\Models\Notulen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
   
    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nama_perusahaan' => 'required|string',
                'nama_direktur' => 'required|string',
                'no_hp_direktur' => 'nullable|string',
                'no_hp_perusahaan' => 'nullable|string',
                'alamat_perusahaan' => 'required|string',
                'logo_perusahaan' => 'nullable|file|mimes:jpg,jpeg,png,pdf',

                'nama_penanggung_jawab' => 'required|string',
                'no_hp_penanggung_jawab' => 'required|string',
                'ktp_penanggung_jawab' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
                'npwp_penanggung_jawab' => 'nullable|file|mimes:jpg,jpeg,png,pdf',

                'nama_pemegang_saham' => 'required|string',
                'no_hp_pemegang_saham' => 'required|string',
                'ktp_pemegang_saham' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
                'npwp_pemegang_saham' => 'nullable|file|mimes:jpg,jpeg,png,pdf',

                'email' => 'required|email|unique:users,email',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                    'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[.,!]).{8,}$/'
                ],

                'is_pengurus' => 'required|boolean',
                'jabatan' => 'required_if:is_pengurus,1|string|nullable',
                'tanggal_mulai_pengurus' => 'required_if:is_pengurus,1|date|nullable',
                'tanggal_akhir_pengurus' => 'required_if:is_pengurus,1|date|nullable',
            ], [
                'password.regex' => 'Password harus mengandung setidaknya satu huruf besar, satu angka, dan satu simbol (.,!).',
                'jabatan.required_if' => 'Jabatan wajib diisi jika pengguna adalah pengurus.',
                'tanggal_mulai_pengurus.required_if' => 'Tanggal mulai pengurus wajib diisi jika pengguna adalah pengurus.',
                'tanggal_akhir_pengurus.required_if' => 'Tanggal akhir pengurus wajib diisi jika pengguna adalah pengurus.',
            ]);

            $user = User::create([
                'nama_perusahaan' => $validatedData['nama_perusahaan'],
                'nama_direktur' => $validatedData['nama_direktur'],
                'no_hp_direktur' => $validatedData['no_hp_direktur'] ?? null,
                'no_hp_perusahaan' => $validatedData['no_hp_perusahaan'] ?? null,
                'alamat_perusahaan' => $validatedData['alamat_perusahaan'],
                'nama_penanggung_jawab' => $validatedData['nama_penanggung_jawab'],
                'no_hp_penanggung_jawab' => $validatedData['no_hp_penanggung_jawab'],
                'nama_pemegang_saham' => $validatedData['nama_pemegang_saham'],
                'no_hp_pemegang_saham' => $validatedData['no_hp_pemegang_saham'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'is_pengurus' => $validatedData['is_pengurus'],
                'jabatan' => $validatedData['is_pengurus'] ? $validatedData['jabatan'] : null,
                'tanggal_mulai_pengurus' => $validatedData['is_pengurus'] ? $validatedData['tanggal_mulai_pengurus'] : null,
                'tanggal_akhir_pengurus' => $validatedData['is_pengurus'] ? $validatedData['tanggal_akhir_pengurus'] : null,
            ]);

            $folderPath = "data_user/{$user->id}";

            $logoPath = $request->file('logo_perusahaan')?->store("{$folderPath}/logo_perusahaan");
            $ktpPenanggungPath = $request->file('ktp_penanggung_jawab')?->store("{$folderPath}/ktp_penanggung_jawab");
            $npwpPenanggungPath = $request->file('npwp_penanggung_jawab')?->store("{$folderPath}/npwp_penanggung_jawab");
            $ktpPemegangPath = $request->file('ktp_pemegang_saham')?->store("{$folderPath}/ktp_pemegang_saham");
            $npwpPemegangPath = $request->file('npwp_pemegang_saham')?->store("{$folderPath}/npwp_pemegang_saham");

            $user->update([
                'logo_perusahaan' => $logoPath,
                'ktp_penanggung_jawab' => $ktpPenanggungPath,
                'npwp_penanggung_jawab' => $npwpPenanggungPath,
                'ktp_pemegang_saham' => $ktpPemegangPath,
                'npwp_pemegang_saham' => $npwpPemegangPath,
            ]);

            return response()->json([
                'message' => 'Registrasi berhasil!',
                'user' => $user,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal!',
                'errors' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat registrasi!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
   
    public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string|min:8',
    ]);

    // === Login sebagai Admin ===
    $admin = Admin::where('email', $credentials['email'])->first();
    if ($admin && Hash::check($credentials['password'], $admin->password)) {
        $expiresAt = now()->addHours(6);
        $token = $admin->createToken('Admin Token', ['admin:access'])->plainTextToken;

        if ($admin->tokens()->latest()->first()) {
            $admin->tokens()->latest()->first()->update(['expires_at' => $expiresAt]);
        }

        return response()->json([
            'message' => 'Login successful as admin',
            'token' => $token,
            'expires_at' => $expiresAt,
            'user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'username' => $admin->username,
                'email' => $admin->email,
                'role' => 'admin'
            ],
        ], 200);
    }

    // === Login sebagai User ===
    $user = User::where('email', $credentials['email'])->first();
    if ($user && Hash::check($credentials['password'], $user->password)) {
        $expiresAt = now()->addHours(2);
        $token = $user->createToken('User Token', ['user:access'])->plainTextToken;

        if ($user->tokens()->latest()->first()) {
            $user->tokens()->latest()->first()->update(['expires_at' => $expiresAt]);
        }

        return response()->json([
            'message' => 'Login successful as user',
            'token' => $token,
            'expires_at' => $expiresAt,
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

    // === Gagal login ===
    return response()->json(['message' => 'Incorrect email or password'], 401);
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
