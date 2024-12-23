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
use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\BeritaController;
use App\Http\Controllers\Api\GaleriController;
use App\Http\Controllers\Api\KomentarController;
use App\Http\Controllers\Api\SbusRegistrationController;
use App\Http\Controllers\Api\RekeningController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SbunRegistrationController;
use App\Http\Controllers\Api\UserDetailController;

//Konten GET untuk Public
Route::get('/agenda', [AgendaController::class, 'index']);
Route::get('/agenda/{id}', [AgendaController::class, 'show']);
Route::get('/berita', [BeritaController::class, 'index']);
Route::get('/berita/{id}', [BeritaController::class, 'show']);
Route::get('/galeri', [GaleriController::class, 'index']);
Route::get('/profile', [ProfileController::class, 'getProfile']);
Route::get('/berita/{berita_id}/komentar', [KomentarController::class, 'index']);
Route::post('/berita/{berita_id}/komentar', [KomentarController::class, 'store']);
Route::get('/detail/all-user', [UserDetailController::class, 'index']);
// Data untuk Public
Route::get('/kota-kabupaten', [KotaKabupatenController::class, 'index']);
Route::get('/rek', [RekeningController::class, 'index']);
Route::get('/klasifikasi', [KlasifikasiController::class, 'index']);
Route::get('/klasifikasi/{id}', [KlasifikasiController::class, 'show']);
Route::get('/detail/klasifikasi', [KlasifikasiController::class, 'detail']);
Route::get('/non-konstruksi/klasifikasis', [NonKonstruksiKlasifikasiController::class, 'index']);
Route::get('/non-konstruksi/klasifikasis/{id}', [NonKonstruksiKlasifikasiController::class, 'show']);
Route::get('/non-konstruksi/{klasifikasiId}/sub-klasifikasis', [NonKonstruksiSubKlasifikasiController::class, 'index']);

// Route Auth (Login & Register)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Middleware untuk Admin Only
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/detail', [UserDetailController::class, 'index']);

    //Search Fitur
    Route::get('/sbu/search', [SbusRegistrationController::class, 'search']);
    Route::get('/kta/search', [KtaController::class, 'search']);
    Route::get('/sbun/search', [SbunRegistrationController::class, 'search']);

    //Validasi KTA
    
    Route::get('/kta', [KtaController::class, 'index']);
    Route::get('/kta/all-pending', [KtaController::class, 'allPending']);
    Route::get('/kta/{id}', [KtaController::class, 'show']);
    Route::put('/kta/approve/{id}', [KTAController::class, 'approveKTA']);
    Route::get('/download-kta/{userId}', [KtaController::class, 'downloadKTAFiles']);
    Route::post('/kta/upload-kta/{id}', [KtaController::class, 'uploadKta']);

    //Validasi SBU Konstruksi
    Route::get('/sbun/all-pending', [SbunRegistrationController::class, 'allPending']);
    Route::get('/sbun/all-active', [SbunRegistrationController::class, 'allActive']);
    Route::get('/sbus/search', [SbusRegistrationController::class, 'search']);
    Route::get('/sbu', [SbusRegistrationController::class, 'index']);
    Route::get('/sbu/{id}', [SbusRegistrationController::class, 'show']);
    Route::put('/sbu/{id}/status', [SbusRegistrationController::class, 'status']);
    Route::get('/sbus/documents/download/{id}', [SbusRegistrationController::class, 'downloadSBUSDocuments']);

    Route::get('/sbus', [SbusRegistrationController::class, 'index']);
    Route::get('/sbus/pending', [SbusRegistrationController::class, 'pending']);
    Route::get('/sbus/active', [SbusRegistrationController::class, 'active']);
    Route::get('/sbus/{id}', [SbusRegistrationController::class, 'show']);
    Route::put('/sbus/{id}/status', [SbusRegistrationController::class, 'status']);
    Route::get('/sbus/{id}/download', [SbusRegistrationController::class, 'downloadSBUSFiles']);

    //Validasi SBU Non Konstruksi
    Route::get('/sbun', [SbunRegistrationController::class, 'index']);
    Route::get('/sbun/pending', [SbunRegistrationController::class, 'pending']);
    Route::get('/sbun/active', [SbunRegistrationController::class, 'active']);
    Route::get('/sbun/{userId}', [SbunRegistrationController::class, 'show']);
    Route::put('/sbun/{id}/status', [SbunRegistrationController::class, 'status']);
    Route::get('/sbun/{id}/download', [SbunRegistrationController::class, 'downloadSBUNFiles']);

    //Rekening Tujuan Routes
    Route::get('/rek/{id}', [RekeningController::class, 'show']);
    Route::post('/rek', [RekeningController::class, 'store']);
    Route::put('/rek/{id}', [RekeningController::class, 'update']);
    Route::delete('/rek/{id}', [RekeningController::class, 'destroy']);

    // Kota Kabupaten
    Route::post('kota-kabupaten', [KotaKabupatenController::class, 'store']);
    Route::get('kota-kabupaten/{id}', [KotaKabupatenController::class, 'show']);
    Route::put('kota-kabupaten/{id}', [KotaKabupatenController::class, 'update']);
    Route::delete('kota-kabupaten/{id}', [KotaKabupatenController::class, 'destroy']);

    // Konstruksi Sub Klasifikasi Routes
    Route::post('klasifikasis/{klasifikasiId}/sub-klasifikasis', [SubKlasifikasiController::class, 'store']);
    Route::put('klasifikasis/{klasifikasiId}/sub-klasifikasis/{subKlasifikasiId}', [SubKlasifikasiController::class, 'update']);
    Route::delete('klasifikasis/{klasifikasiId}/sub-klasifikasis/{subKlasifikasiId}', [SubKlasifikasiController::class, 'destroy']);

    // Konstruksi Klasifikasi Routes
    Route::post('/klasifikasi', [KlasifikasiController::class, 'store']); // Tambah klasifikasi baru
    Route::put('/klasifikasi/{id}', [KlasifikasiController::class, 'update']); // Update klasifikasi
    Route::delete('/klasifikasi/{id}', [KlasifikasiController::class, 'destroy']); // Hapus klasifikasi

    // Klasifikasi Non Konstruksi Routes    
    Route::post('non-konstruksi/klasifikasis', [NonKonstruksiKlasifikasiController::class, 'store']);
    Route::put('non-konstruksi/klasifikasis/{id}', [NonKonstruksiKlasifikasiController::class, 'update']);
    Route::delete('non-konstruksi/klasifikasis/{id}', [NonKonstruksiKlasifikasiController::class, 'destroy']);

    // Sub Klasifikasi Non Konstruksi Routes
    Route::post('non-konstruksi/klasifikasis/{klasifikasiId}/sub-klasifikasis', [NonKonstruksiSubKlasifikasiController::class, 'store']);
    Route::put('non-konstruksi/klasifikasis/{klasifikasiId}/sub-klasifikasis/{subKlasifikasiId}', [NonKonstruksiSubKlasifikasiController::class, 'update']);
    Route::delete('non-konstruksi/klasifikasis/{klasifikasiId}/sub-klasifikasis/{subKlasifikasiId}', [NonKonstruksiSubKlasifikasiController::class, 'destroy']);

    //Content Route//

    // Route Agenda
    Route::post('/agenda', [AgendaController::class, 'store']);
    Route::put('/agenda/{id}', [AgendaController::class, 'update']);
    Route::delete('/agenda/{id}', [AgendaController::class, 'destroy']);

    // Route Berita
    Route::post('/berita', [BeritaController::class, 'store']);
    Route::delete('/berita/{id}', [BeritaController::class, 'destroy']);

    // Route Profile
    Route::post('/profile', [ProfileController::class, 'store']);
    Route::put('/profile/{id}', [ProfileController::class, 'update']);
    Route::delete('/profile/{id}', [ProfileController::class, 'destroy']);

    //Route Galeri
    Route::post('/galeri', [GaleriController::class, 'store']);
    Route::put('/galeri/{id}', [GaleriController::class, 'update']);
    Route::delete('/galeri/{id}', [GaleriController::class, 'destroy']);
});


Route::middleware(['auth:sanctum', 'user'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/kta', [KtaController::class, 'store']);
    Route::post('kta/{id}/extend', [KtaController::class, 'extend']);
    Route::get('/getKta', [KtaController::class, 'getKTA']);

    Route::post('/sbus', [SbusRegistrationController::class, 'store']);
    Route::post('/sbun', [SbunRegistrationController::class, 'store']);
    Route::get('/getSbus', [SbusRegistrationController::class, 'getSbus']);
    Route::get('/getSbun', [SbunRegistrationController::class, 'getSbun']);
});
