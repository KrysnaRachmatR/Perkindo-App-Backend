<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GaleriController;
use App\Http\Controllers\Api\MemberAuthController;
use App\Http\Controllers\Auth\MemberRegistrationController;
use App\Http\Controllers\Api\SbuCodeController;
use App\Http\Controllers\Api\KlasifikasiController;
use App\Http\Controllers\Api\SubKlasifikasiController;

// Rute pendaftaran anggota
Route::post('/anggota/register', [MemberRegistrationController::class, 'register'])->name('anggota.register');

// Rute login untuk admin
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Rute login untuk anggota
Route::post('/anggota/login', [MemberAuthController::class, 'login'])->name('anggota.login');

// Rute yang dilindungi oleh middleware auth:sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Rute logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/anggota/logout', [MemberAuthController::class, 'logout'])->name('anggota.logout');

    // CRUD Galeri
    Route::get('/galeri', [GaleriController::class, 'index'])->name('galeri.index');
    Route::post('/galeri', [GaleriController::class, 'store'])->name('galeri.store');
    Route::get('/galeri/{id}', [GaleriController::class, 'show'])->name('galeri.show');
    Route::put('/galeri/{id}', [GaleriController::class, 'update'])->name('galeri.update');
    Route::delete('/galeri/{id}', [GaleriController::class, 'destroy'])->name('galeri.destroy');

    // CRUD Klasifikasi
    Route::get('/klasifikasis', [KlasifikasiController::class, 'index'])->name('klasifikasis.index');
    Route::post('/klasifikasis', [KlasifikasiController::class, 'store'])->name('klasifikasis.store');
    Route::get('/klasifikasis/{id}', [KlasifikasiController::class, 'show'])->name('klasifikasis.show');
    Route::put('/klasifikasis/{id}', [KlasifikasiController::class, 'update'])->name('klasifikasis.update');
    Route::delete('/klasifikasis/{id}', [KlasifikasiController::class, 'destroy'])->name('klasifikasis.destroy');

    // CRUD Sub Klasifikasi
    Route::get('/sub-klasifikasis', [SubKlasifikasiController::class, 'index'])->name('sub-klasifikasis.index');
    Route::post('/sub-klasifikasis', [SubKlasifikasiController::class, 'store'])->name('sub-klasifikasis.store');
    Route::get('/sub-klasifikasis/{id}', [SubKlasifikasiController::class, 'show'])->name('sub-klasifikasis.show');
    Route::put('/sub-klasifikasis/{id}', [SubKlasifikasiController::class, 'update'])->name('sub-klasifikasis.update');
    Route::delete('/sub-klasifikasis/{id}', [SubKlasifikasiController::class, 'destroy'])->name('sub-klasifikasis.destroy');

    // CRUD SBU Codes
    Route::get('/sbu-codes', [SbuCodeController::class, 'index'])->name('sbu-codes.index');
    Route::post('/sbu-codes', [SbuCodeController::class, 'store'])->name('sbu-codes.store');
    Route::get('/sbu-codes/{id}', [SbuCodeController::class, 'show'])->name('sbu-codes.show');
    Route::put('/sbu-codes/{id}', [SbuCodeController::class, 'update'])->name('sbu-codes.update');
    Route::delete('/sbu-codes/{id}', [SbuCodeController::class, 'destroy'])->name('sbu-codes.destroy');

    // Menambahkan sub klasifikasi baru dengan kode SBU dan KBLI ke klasifikasi tertentu
    Route::post('/klasifikasis/{id}/add-sub-klasifikasi', [KlasifikasiController::class, 'addSubKlasifikasiWithSbu'])->name('klasifikasis.add-sub-klasifikasi');

    // Relasi dan Pencarian Klasifikasi dengan Sub Klasifikasi dan SBU
    Route::get('/klasifikasis/{id}/sub-klasifikasis', [KlasifikasiController::class, 'getSubKlasifikasis'])->name('klasifikasis.sub-klasifikasis');
    Route::get('/sub-klasifikasis/{id}/sbu-codes', [SubKlasifikasiController::class, 'getSbuCodes'])->name('sub-klasifikasis.sbu-codes');

    // Menyimpan klasifikasi dengan sub klasifikasi dan SBU
    Route::post('/klasifikasi', [KlasifikasiController::class, 'storeWithDetails'])->name('klasifikasi.storeWithDetails');
});
