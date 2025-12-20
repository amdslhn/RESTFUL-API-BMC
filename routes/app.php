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
use App\Http\Controllers\CatatanPartografController;
use App\Http\Controllers\KontraksiController;
use App\Http\Controllers\KontenEdukasiController;
use App\Http\Controllers\PesanController;


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

    //
    Route::get('/pasien/{no_reg}/progres-persalinan', [PasienController::class, 'lihatProgres']);

    //get data pasien by no_reg
    Route::get('/pasien/{no_reg}/getData', [PasienController::class, 'getPasienById']);


    // Konten Edukasi untuk Pasien
    Route::get('/konten-edukasi', [KontenEdukasiController::class, 'index']);
    Route::get('/konten-edukasi/{id-konten}', [KontenEdukasiController::class, 'show']);
    Route::get('/partograf/{id}/catatan', [CatatanPartografController::class, 'getCatatanByPartograf']);

    // fitur Chat
    Route::post('/pesan/{pengirimId}/{penerimaId}', [PesanController::class, 'kirimPesan']);
    Route::get('/pesan/{bidanId}/{pasienId}', [PesanController::class, 'ambilPesan']);

    //get bidan by pasien no_reg
    Route::get('/pasien/{no_reg}/bidanId', [PasienController::class, 'getBidan']);

    Route::post('/darurat', [PasienController::class, 'darurat']);


});

Route::middleware([JwtCookieMiddleware::class, BidanMiddleware::class])->group(function () {
    Route::post('/bidan/register-pasien', [BidanController::class, 'registerPasien']);
    Route::get('/bidan/pasien', [BidanController::class, 'lihatDaftarPasien']);
    Route::post('/bidan/pasien/{pasienId}/mulai-persalinan', [BidanController::class, 'mulaiPersalinan']);

    // Catatan Partograf
    Route::post('/partograf/{id}/catatan', [CatatanPartografController::class, 'buatCatatanPartograf']);

    Route::post('/catatan-partograf/{id}/kontraksi', [KontraksiController::class, 'store']);

    Route::post('/darurat/{id}/konfirmasi', [BidanController::class, 'konfirmasiDarurat']);

    Route::post('/bidan/update-token', [BidanController::class, 'updateToken']);

    // Konten Edukasi
    Route::post('/konten-edukasi', [KontenEdukasiController::class, 'store']);
    Route::delete('/konten-edukasi/{id}', [KontenEdukasiController::class, 'destroy']);

});