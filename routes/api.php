<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\KotaKabupatenController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KlasifikasiController;
use App\Http\Controllers\Api\KtaController;
use App\Http\Controllers\Api\SubKlasifikasiController;
use App\Http\Controllers\Api\NonKonstruksiKlasifikasiController;
use App\Http\Controllers\Api\NonKonstruksiSubKlasifikasiController;
use App\Http\Controllers\Api\InstagramController;

Route::get('/kota-kabupaten', [KotaKabupatenController::class, 'index']);

// Route Auth (Login & Register)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('/instagram/media', [InstagramController::class, 'getMedia']);
Route::post('/instagram/refresh', [InstagramController::class, 'serviceRefresh']);

// Middleware untuk Admin Only
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    //Validasi KTA
    Route::post('kta', [KtaController::class, 'store']);
    Route::put('kta/{id}', [KtaController::class, 'update']);
    Route::post('kta/{id}/extend', [KtaController::class, 'extend']);
    Route::put('kta/{id}/approve', [KtaController::class, 'approveExtension']);
    Route::put('kta/{id}/reject', [KtaController::class, 'rejectExtension']);
    Route::get('kta', [KtaController::class, 'index']);

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

    // Klasifikasi Non Konstruksi Routes
    Route::get('non-konstruksi/klasifikasis', [NonKonstruksiKlasifikasiController::class, 'indexWithSubKlasifikasiAndCodes']);
    Route::post('non-konstruksi/klasifikasis', [NonKonstruksiKlasifikasiController::class, 'store']);
    Route::get('non-konstruksi/klasifikasis/{id}', [NonKonstruksiKlasifikasiController::class, 'show']);
    Route::put('non-konstruksi/klasifikasis/{id}', [NonKonstruksiKlasifikasiController::class, 'update']);
    Route::delete('non-konstruksi/klasifikasis/{id}', [NonKonstruksiKlasifikasiController::class, 'destroy']);

    // Sub Klasifikasi Non Konstruksi Routes
    Route::get('non-konstruksi/klasifikasis/{klasifikasiId}/sub-klasifikasis', [NonKonstruksiSubKlasifikasiController::class, 'index']);
    Route::post('non-konstruksi/klasifikasis/{klasifikasiId}/sub-klasifikasis', [NonKonstruksiSubKlasifikasiController::class, 'store']);
    Route::get('non-konstruksi/klasifikasis/{klasifikasiId}/sub-klasifikasis/{subKlasifikasiId}', [NonKonstruksiSubKlasifikasiController::class, 'show']);
    Route::put('non-konstruksi/klasifikasis/{klasifikasiId}/sub-klasifikasis/{subKlasifikasiId}', [NonKonstruksiSubKlasifikasiController::class, 'update']);
    Route::delete('non-konstruksi/klasifikasis/{klasifikasiId}/sub-klasifikasis/{subKlasifikasiId}', [NonKonstruksiSubKlasifikasiController::class, 'destroy']);
});


Route::middleware(['auth:sanctum', 'user'])->group(function () {
    Route::post('/kta', [KtaController::class, 'store'])->name('kta.store');
    Route::put('/{kta}', [KtaController::class, 'update'])->name('kta.update');
    Route::post('kta/{id}/extend', [KtaController::class, 'extend']);
});
