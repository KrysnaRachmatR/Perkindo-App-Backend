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

Route::get('/kota-kabupaten', [KotaKabupatenController::class, 'index']);

// Route Auth (Login & Register)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('/instagram/media', [InstagramController::class, 'getMedia']);
Route::post('/instagram/refresh', [InstagramController::class, 'serviceRefresh']);
Route::post('/berita/{berita_id}/komentar', [KomentarController::class, 'store']);
Route::get('/berita/{berita_id}/komentar', [KomentarController::class, 'index']);

// Middleware untuk Admin Only
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    //Validasi KTA
    Route::get('/kta', [KtaController::class, 'index']); // Menampilkan semua pengajuan KTA
    Route::get('/kta/{kta}', [KtaController::class, 'show']);
    Route::put('/kta/{kta}', [KtaController::class, 'update']);

    //Validasi SBU Konstruksi
    Route::get('sbu-konstruksi', [SbusRegistrationController::class, 'index']);
    Route::get('sbu-konstruksi/{id}', [SbusRegistrationController::class, 'show']);
    Route::put('sbu-konstruksi/{id}', [SbusRegistrationController::class, 'update']);
    Route::delete('sbu-konstruksi/{id}', [SbusRegistrationController::class, 'destroy']);

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

    // Route Agenda
    Route::get('/agendas', [AgendaController::class, 'index']); // Menampilkan semua agenda
    Route::post('/agendas', [AgendaController::class, 'store']); // Menambah agenda
    Route::get('/agendas/{id}', [AgendaController::class, 'show']); // Menampilkan agenda berdasarkan ID
    Route::put('/agendas/{id}', [AgendaController::class, 'update']); // Memperbarui agenda
    Route::delete('/agendas/{id}', [AgendaController::class, 'destroy']); // Menghapus agenda

    // Route Berita
    Route::get('/beritas', [BeritaController::class, 'index']); // Menampilkan semua berita
    Route::post('/beritas', [BeritaController::class, 'store']); // Menambahkan berita
    Route::get('/beritas/{id}', [BeritaController::class, 'show']); // Menampilkan berita berdasarkan ID
    Route::delete('/beritas/{id}', [BeritaController::class, 'destroy']); // Menghapus berita
    Route::get('/berita/{berita_id}/komentar', [KomentarController::class, 'index']);
});


Route::middleware(['auth:sanctum', 'user'])->group(function () {
    Route::post('/kta', [KtaController::class, 'store']); // Mengajukan KTA
    Route::put('/kta/{kta}/extend', [KtaController::class, 'extend']); // Mengajukan perpanjangan KTA

    Route::post('/sbu-konstruksi', [SbusRegistrationController::class, 'store']);
    Route::get('/sbu-konstruksi', [SbusRegistrationController::class, 'index']);
    Route::get('/sbu-konstruksi/{id}', [SbusRegistrationController::class, 'show']);
});
