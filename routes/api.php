<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Rute login
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Rute logout dan lainnya yang dilindungi oleh middleware admin
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // Tambahkan rute admin lainnya di sini
});
