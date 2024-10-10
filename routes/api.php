<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GaleriController;
use App\Http\Controllers\Api\MemberAuthController;
use App\Http\Controllers\Auth\MemberRegistrationController;

// Rute pendaftaran anggota
Route::post('/anggota/register', [MemberRegistrationController::class, 'register'])->name('anggota.register');

// Rute login untuk admin
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Rute login untuk anggota
Route::post('/anggota/login', [MemberAuthController::class, 'login'])->name('anggota.login');

// Rute yang dilindungi oleh middleware
Route::middleware('auth:sanctum')->group(function () {
    // Rute logout untuk admin
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Rute logout untuk anggota
    Route::post('/anggota/logout', [MemberAuthController::class, 'logout'])->name('anggota.logout');

    // CRUD Galeri
    Route::get('/galeri', [GaleriController::class, 'index'])->name('galeri.index');
    Route::post('/galeri', [GaleriController::class, 'store'])->name('galeri.store');
    Route::get('/galeri/{id}', [GaleriController::class, 'show'])->name('galeri.show');
    Route::put('/galeri/{id}', [GaleriController::class, 'update'])->name('galeri.update');
    Route::delete('/galeri/{id}', [GaleriController::class, 'destroy'])->name('galeri.destroy');
});
