<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SbuCodeController;
use App\Http\Controllers\Api\KlasifikasiController;
use App\Http\Controllers\Api\SubKlasifikasiController;

// Route Auth (Login & Register)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Middleware untuk Admin Only
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Sub Klasifikasi Routes
    Route::get('klasifikasis/{klasifikasiId}/sub-klasifikasis', [SubKlasifikasiController::class, 'index']);
    Route::post('klasifikasis/{klasifikasiId}/sub-klasifikasis', [SubKlasifikasiController::class, 'store']);
    Route::get('klasifikasis/{klasifikasiId}/sub-klasifikasis/{subKlasifikasiId}', [SubKlasifikasiController::class, 'show']);
    Route::put('klasifikasis/{klasifikasiId}/sub-klasifikasis/{subKlasifikasiId}', [SubKlasifikasiController::class, 'update']);
    Route::delete('klasifikasis/{klasifikasiId}/sub-klasifikasis/{subKlasifikasiId}', [SubKlasifikasiController::class, 'destroy']);

    // Klasifikasi Routes
    Route::get('klasifikasis', [KlasifikasiController::class, 'index']);
    Route::get('/klasifikasis/all', [KlasifikasiController::class, 'indexWithSubKlasifikasiAndCodes']);
    Route::post('klasifikasis', [KlasifikasiController::class, 'store']);
    Route::get('klasifikasis/{id}', [KlasifikasiController::class, 'show']);
    Route::put('klasifikasis/{id}', [KlasifikasiController::class, 'update']);
    Route::delete('klasifikasis/{id}', [KlasifikasiController::class, 'destroy']);

    // **SBU Code Routes**
    Route::get('sbu-codes', [SbuCodeController::class, 'index']); // Semua SBU Codes
    Route::post('sbu-codes', [SbuCodeController::class, 'store']); // Tambah SBU Code baru
    Route::get('sbu-codes/{id}', [SbuCodeController::class, 'show']); // Detail SBU Code
    Route::put('sbu-codes/{id}', [SbuCodeController::class, 'update']); // Update SBU Code
    Route::delete('sbu-codes/{id}', [SbuCodeController::class, 'destroy']); // Hapus SBU Code
    Route::get('sbu-codes/search', [SbuCodeController::class, 'search']); // Pencarian SBU Code
});
