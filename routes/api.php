<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GaleriController;
use App\Http\Controllers\Auth\MemberRegistrationController;

// Rute pendaftaran anggota
Route::post('/anggota/register', [MemberRegistrationController::class, 'register'])->name('anggota.register');

// Rute login
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // CRUD Galeri
    Route::get('/galeri', [GaleriController::class, 'index']);
    Route::post('/galeri', [GaleriController::class, 'store']);
    Route::get('/galeri/{id}', [GaleriController::class, 'show']);
    Route::put('/galeri/{id}', [GaleriController::class, 'update']);
    Route::delete('/galeri/{id}', [GaleriController::class, 'destroy']);
});
