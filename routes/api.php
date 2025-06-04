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
use App\Http\Controllers\Api\RapatController;

Route::get('/rapat', [RapatController::class, 'index']);

//Konten GET untuk Public
Route::get('/agenda', [AgendaController::class, 'index']);
Route::get('/agenda/{id}', [AgendaController::class, 'show']);
Route::get('/berita', [BeritaController::class, 'index']);
Route::get('/berita/{id}', [BeritaController::class, 'show']);
Route::get('/galeri', [GaleriController::class, 'index']);
Route::get('/profile', [ProfileController::class, 'getProfile']);
Route::get('/berita/{berita_id}/komentar', [KomentarController::class, 'index']);
Route::post('/berita/{berita_id}/komentar', [KomentarController::class, 'store']);

// Data untuk Public
Route::get('/kota-kabupaten', [KotaKabupatenController::class, 'index']);
Route::get('/rek', [RekeningController::class, 'index']);
Route::get('/klasifikasi', [KlasifikasiController::class, 'index']);
Route::get('/klasifikasi/{id}', [KlasifikasiController::class, 'show']);
Route::get('/detail/klasifikasi', [KlasifikasiController::class, 'detail']);
Route::get('/non-konstruksi/klasifikasis', [NonKonstruksiKlasifikasiController::class, 'index']);
Route::get('/non-konstruksi/klasifikasis/{id}', [NonKonstruksiKlasifikasiController::class, 'show']);
Route::get('/non-konstruksi/{klasifikasiId}/sub-klasifikasis', [NonKonstruksiSubKlasifikasiController::class, 'index']);
Route::get('/detail-non', [UserDetailController::class, 'indexNonKonstruksi']);
Route::get('/detail', [UserDetailController::class, 'indexKonstruksi']);

// Route Auth (Login & Register)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Middleware untuk Admin Only
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    //SKRIPSI
    Route::post('/rapat', [RapatController::class, 'store']);
    Route::delete('/rapat/undangan/{id}', [RapatController::class, 'destroy']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // User Management
    Route::get('/total-summary', [UserDetailController::class, 'getDashboardSummary']);

    //Validasi KTA DONE!
    Route::get('/kta', [KtaController::class, 'index']);
    Route::get('/kta/all-pending', [KtaController::class, 'allPending']);
    Route::put('/kta/approve/{id}', [KTAController::class, 'approveKTA']);
    Route::get('/kta/download/{userId}', [KtaController::class, 'downloadFile']);
    Route::post('/kta/upload/{id}', [KtaController::class, 'uploadKta']);

    //Validasi SBU Non Konstruksi DONE!
    Route::get('/sbun/all-pending', [SbunRegistrationController::class, 'allPending']);
    Route::get('/sbun/active', [SbunRegistrationController::class, 'active']);
    Route::put('/sbun/{id}/status', [SbusRegistrationController::class, 'status']);
    Route::get('/sbun/download/{registrationId}', [SbusRegistrationController::class, 'downloadSBUSFiles']);
    
    //Validasi SBU Konstruksi
    Route::get('/sbus/pending', [SbusRegistrationController::class, 'pending']);
    Route::get('/sbus/active', [SbusRegistrationController::class, 'active']);
    Route::get('/sbus/expired', [SbusRegistrationController::class, 'expired']);
    Route::put('/sbus/{id}/status', [SbusRegistrationController::class, 'status']);
    Route::get('/sbus/download/{registrationId}', [SbusRegistrationController::class, 'downloadSBUSFiles']);
    
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
    //SKRIPSI
    Route::get('/rapat/undangan', [RapatController::class, 'undanganRapat']);
    Route::post('/rapat/{rapatId}/vote-tanggal', [PollingTanggalController::class, 'voteTanggal']);
    //--{}---//

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/update/profile', [AuthController::class, 'updateProfile']);

    Route::post('/kta', [KtaController::class, 'store']);
    Route::get('/kta/showDetail', [KtaController::class, 'checkDetail']);
    Route::post('/kta/extend', [KtaController::class, 'extend']);

    Route::post('/sbus', [SbusRegistrationController::class, 'store']);
    Route::post('/sbun', [SbunRegistrationController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'notulen'])->group(function () {
    Route::post('/notes', [MeetingNoteController::class, 'store']);
});

    Route::post('/poll/respond', [PollingController::class, 'respond']);


    Route::get('/tes-email', function () {
    Mail::raw('Tes kirim email Laravel', function ($message) {
        $message->to('krysnarachmat1@gmail.com')
                ->subject('Tes Email dari Laravel');
    });
});
