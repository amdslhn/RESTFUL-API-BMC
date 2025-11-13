<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BidanController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\JwtCookieMiddleware;
use App\Http\Middleware\BidanMiddleware;
use Illuminate\Http\Request;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\PersalinanController;


// Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-pasien', [PasienController::class, 'login']);
Route::post('/login-bidan', [BidanController::class, 'login']);



Route::middleware([JwtCookieMiddleware::class])->group(function () {   
    // Profil
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/ubah-password', [ProfileController::class, 'updatePassword']);
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Persalinan
    Route::get('/persalinan', [PersalinanController::class, 'index']);
    Route::put('/persalinan/{id}/status', [PersalinanController::class, 'ubahStatus']);
});

Route::middleware([JwtCookieMiddleware::class, BidanMiddleware::class])->group(function () {
    Route::post('/bidan/register-pasien', [BidanController::class, 'registerPasien']);
    Route::get('/bidan/pasien', [BidanController::class, 'lihatDaftarPasien']);
    Route::post('/bidan/pasien/{pasienId}/mulai-persalinan', [BidanController::class, 'mulaiPersalinan']);
});
