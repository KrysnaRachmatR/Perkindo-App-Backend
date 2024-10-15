<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GaleriController;
use App\Http\Controllers\Api\SbuCodeController;
use App\Http\Controllers\Api\KlasifikasiController;
use App\Http\Controllers\Api\SubKlasifikasiController;


Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // CRUD Galeri
    Route::apiResource('galeri', GaleriController::class);

    // CRUD Klasifikasi
    Route::apiResource('klasifikasis', KlasifikasiController::class);
    Route::post('klasifikasis/{id}/add-sub-klasifikasi', [KlasifikasiController::class, 'addSubKlasifikasiWithSbu']);

    // CRUD Sub Klasifikasi
    Route::apiResource('sub-klasifikasis', SubKlasifikasiController::class);

    // CRUD SBU Codes
    Route::apiResource('sbu-codes', SbuCodeController::class);

    // Pencarian dan Relasi
    Route::get('klasifikasis/search', [KlasifikasiController::class, 'search']);
    Route::get('sub-klasifikasis/search', [SubKlasifikasiController::class, 'search']);
    Route::get('sbu-codes/search', [SbuCodeController::class, 'search']);
    Route::get('klasifikasis/{id}/sub-klasifikasis', [KlasifikasiController::class, 'getSubKlasifikasis']);
    Route::get('sub-klasifikasis/{id}/sbu-codes', [SubKlasifikasiController::class, 'getSbuCodes']);
});
