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

    public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $bidan = $this->bidanService->login($request->only('username','password'));

    if (!$bidan) {
        return response()->json(['error' => 'Username atau password salah'], 401);
    }

    $customClaims = [
        'sub' => (string) $bidan->id,
        'role' => 'bidan',
        'username' => $bidan->username,
        'nama' => $bidan->nama,
    ];

    $token = auth('bidan')->claims($customClaims)->fromUser($bidan);
    $cookie = cookie('token', $token, 60 * 24);

    return response()->json([
        'message' => 'Login berhasil',
        'bidan' => [
            'id' => $bidan->id,
            'username' => $bidan->username,
            'nama' => $bidan->nama,
        ],
        'token' => $token
    ])->withCookie($cookie);
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
            'no_reg'   => 'required|string|unique:pasien,no_reg',
            'nama'     => 'required|string|max:100',
            'password' => 'nullable|string|min:6',
            'alamat'   => 'required|string|max:60',
            'umur'     => 'required|numeric',
            'gravida'  => 'required|numeric',
            'paritas'  => 'required|numeric',
            'abortus'  => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $bidan = $request->auth_user;

        $data = $request->only([
            'no_reg', 'nama', 'password', 'alamat', 'umur',
            'gravida', 'paritas', 'abortus'
        ]);

        $pasien = $this->bidanService->tambahPasien($data, $bidan);

        return response()->json([
            'message' => 'Pasien berhasil didaftarkan',
            'pasien' => $pasien
        ]);
    }
    public function mulaiPersalinan(Request $request, $pasienId)
{
    $bidan = $request->auth_user; // dari JWT middleware
    $pasien = Pasien::find($pasienId);

    if (!$pasien) {
        return response()->json(['error' => 'Pasien tidak ditemukan.'], 404);
    }

    try {
        $hasil = $this->bidanService->mulaiPersalinan($request, $bidan, $pasien);

        return response()->json([
            'message' => 'Persalinan berhasil dimulai.',
            'data' => $hasil,
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'errors' => $e->errors()
        ], 422);
    }
}

    public function konfirmasiDarurat(Request $request, $idDarurat)
    {
        $bidan = auth('bidan')->user();

        // PANGGIL FUNCTION MODEL SESUAI DIAGRAM
        $berhasil = $bidan->konfirmasiDarurat($idDarurat);

        if ($berhasil) {
            return response()->json(['message' => 'Status darurat diselesaikan.'], 200);
        } else {
            return response()->json(['message' => 'Gagal konfirmasi. Data tidak ditemukan.'], 404);
        }
    }

    public function updateToken(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Ambil bidan yang sedang login (dari token JWT)
        // Pastikan middleware auth:bidan aktif di route
        $bidan = auth('bidan')->user();
        
        if (!$bidan) {
             return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Simpan token Expo ke database
        $bidan->update(['fcm_token' => $request->token]);

        return response()->json(['message' => 'Token notifikasi berhasil disimpan']);
    }

}
