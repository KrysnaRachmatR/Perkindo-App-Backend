<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SbuKonstruksiController;
use App\Http\Controllers\Api\SbuNonKonstruksiController;
use App\Http\Controllers\Api\GaleriController;

// Rute login
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/sbu-konstruk', [SbuKonstruksiController::class, 'indexPublic']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // CRUD Konstruksi
    Route::get('/sbu-konstruksi', [SbuKonstruksiController::class, 'index']);
    Route::post('/sbu-konstruksi', [SbuKonstruksiController::class, 'store']);
    Route::get('/sbu-konstruksi/{id}', [SbuKonstruksiController::class, 'show']);
    Route::put('/sbu-konstruksi/{id}', [SbuKonstruksiController::class, 'update']);
    Route::delete('/sbu-konstruksi/{id}', [SbuKonstruksiController::class, 'destroy']);
    Route::get('/sbu-konstruksi/count', [SbuKonstruksiController::class, 'count']);
    // CRUD Non Konstruksi
    Route::get('/sbu-non-konstruksi', [SbuNonKonstruksiController::class, 'index']);
    Route::post('/sbu-non-konstruksi', [SbuNonKonstruksiController::class, 'store']);
    Route::get('/sbu-non-konstruksi/{id}', [SbuNonKonstruksiController::class, 'show']);
    Route::put('/sbu-non-konstruksi/{id}', [SbuNonKonstruksiController::class, 'update']);
    Route::delete('/sbu-non-konstruksi/{id}', [SbuNonKonstruksiController::class, 'destroy']);
    Route::get('/sbu-non-konstruksi/count', [SbuKonstruksiController::class, 'count']);
    // CRUD Galeri
    Route::get('/galeri', [GaleriController::class, 'index']);
    Route::post('/galeri', [GaleriController::class, 'store']);
    Route::get('/galeri/{id}', [GaleriController::class, 'show']);
    Route::put('/galeri/{id}', [GaleriController::class, 'update']);
    Route::delete('/galeri/{id}', [GaleriController::class, 'destroy']);
});
