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
use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\BeritaController;
use App\Http\Controllers\Api\KomentarController;
use App\Http\Controllers\Api\SbusRegistrationController;
use App\Http\Controllers\Api\RekeningController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SbunRegistrationController;

Route::get('/kota-kabupaten', [KotaKabupatenController::class, 'index']);

// Route Auth (Login & Register)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

//API Instagram
Route::get('/instagram/media', [InstagramController::class, 'getMedia']);
Route::post('/instagram/refresh', [InstagramController::class, 'serviceRefresh']);
//Komentar Berita
Route::get('/berita', [BeritaController::class, 'index']);
Route::get('/beritas/{id}', [BeritaController::class, 'show']);
Route::post('/berita/{berita_id}/komentar', [KomentarController::class, 'store']);
Route::get('/berita/{berita_id}/komentar', [KomentarController::class, 'index']);
Route::get('/profile', [ProfileController::class, 'getProfile']);
//Agenda
Route::get('/agenda', [AgendaController::class, 'index']);
Route::get('/agendas/{id}', [AgendaController::class, 'show']);



// Middleware untuk Admin Only
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    //Search Fitur
    Route::get('/sbu/search', [SbusRegistrationController::class, 'search']);
    Route::get('/kta/search', [KtaController::class, 'search']);
    Route::get('/sbun/search', [SbunRegistrationController::class, 'search']);

    //Validasi KTA
    Route::get('/kta', [KtaController::class, 'index'])->name('admin.kta.index');
    Route::get('/kta/{id}', [KtaController::class, 'show']);
    Route::post('kta/{id}/approval', [KtaController::class, 'approveOrReject']);
    Route::put('/kta/approve/{id}', [KTAController::class, 'approveKTA']);

    //Validasi SBU Konstruksi
    Route::get('/sbu', [SbusRegistrationController::class, 'index']);
    Route::get('/sbu/{id}', [SbusRegistrationController::class, 'show']);
    Route::put('/sbu/{id}/status', [SbusRegistrationController::class, 'status']);
    Route::delete('/sbu/{id}', [SbusRegistrationController::class, 'destroy']);

    //Validasi SBU Non Konstruksi
    Route::get('/sbun', [SbunRegistrationController::class, 'index']);
    Route::get('/sbun/{id}', [SbunRegistrationController::class, 'show']);
    Route::put('/sbun/{id}/status', [SbunRegistrationController::class, 'status']);

    //Rekening Tujuan Routes
    Route::get('/rek', [RekeningController::class, 'index']);
    Route::post('/rek', [RekeningController::class, 'store']);
    Route::get('/rek/{id}', [RekeningController::class, 'show']);
    Route::put('/rek/{id}', [RekeningController::class, 'update']);
    Route::delete('/rek/{id}', [RekeningController::class, 'destroy']);

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

    //Content Route//

    // Route Agenda
    Route::post('/agenda', [AgendaController::class, 'store']);
    Route::put('/agenda/{id}', [AgendaController::class, 'update']);
    Route::delete('/agenda/{id}', [AgendaController::class, 'destroy']);

    // Route Berita
    Route::post('/berita', [BeritaController::class, 'store']);
    Route::delete('/berita/{id}', [BeritaController::class, 'destroy']);

    // CRUD Konten Profile
    Route::post('/profile', [ProfileController::class, 'store']);
    Route::put('/profile/{id}', [ProfileController::class, 'update']);
    Route::delete('/profile/{id}', [ProfileController::class, 'destroy']);
});


Route::middleware(['auth:sanctum', 'user'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/kta', [KtaController::class, 'store']);
    Route::post('kta/{id}/extend', [KtaController::class, 'extend']);

    Route::post('/sbu', [SbusRegistrationController::class, 'store']);
    Route::post('/sbun', [SbunRegistrationController::class, 'store']);
});
