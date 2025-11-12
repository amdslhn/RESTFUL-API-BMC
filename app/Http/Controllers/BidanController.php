<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pasien;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\BidanService;

class BidanController extends Controller
{
    protected $bidanService;
    public function __construct(BidanService $bidanService)
    {
        $this->bidanService = $bidanService;
    }
    public function lihatDaftarPasien(Request $request)
    {
        // Ambil user yang login (sudah didekode di JwtCookieMiddleware)
        $bidan = $request->auth_user;

        // Jalankan service-nya
        $daftarPasien = $this->bidanService->lihatDaftarPasien($bidan);

        // Response JSON
        return response()->json([
            'bidan' => [
                // 'id' => $bidan->id,
                'nama' => $bidan->nama,
            ],
            'daftar_pasien' => $daftarPasien
        ]);
    }
    public function registerPasien(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_reg' => 'required|numeric|unique:pasien,no_reg',
            'username' => 'required|string|max:25|unique:pasien,username',
            'nama' => 'required|string|max:100',
            'password' => 'nullable|string|min:6',
            'alamat' => 'required|string|max:25',
            'umur' => 'required|numeric',
            'gravida' => 'required|numeric',
            'paritas' => 'required|numeric',
            'abortus' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $bidan = $request->auth_user; // ambil dari middleware
        $userId = $bidan->id;

        $pasien = Pasien::create([
            'no_reg' => $request->no_reg,
            'username' => $request->username,
            'nama' => $request->nama,
            'password' => Hash::make($request->username), // password auto sama username
            'alamat' => $request->alamat,
            'umur' => $request->umur,
            'gravida' => $request->gravida,
            'paritas' => $request->paritas,
            'abortus' => $request->abortus,
            'bidan_id' => $userId
        ]);

        return response()->json([
            'message' => 'Pasien berhasil didaftarkan',
            'pasien' => $pasien
        ]);
    }
}
