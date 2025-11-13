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
        // Validasi request
        $validator = Validator::make($request->all(), [
            'no_reg' => 'required|string|unique:pasien,no_reg',
            'nama' => 'required|string|max:100',
            'password' => 'nullable|string|min:6',
            'alamat' => 'required|string|max:60',
            'umur' => 'required|numeric',
            'gravida' => 'required|numeric',
            'paritas' => 'required|numeric',
            'abortus' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $bidan = $request->auth_user; // Ambil dari middleware
        $data = $request->only(['no_reg', 'nama', 'password', 'alamat', 'umur', 'gravida', 'paritas', 'abortus']);

        // Panggil service untuk membuat pasien
        $pasien = $this->bidanService->createPasien($data, $bidan);

        return response()->json([
            'message' => 'Pasien berhasil didaftarkan',
            'pasien' => $pasien
        ]);
    }
    public function mulaiPersalinan(Request $request, $pasienId)
{
    $bidan = $request->auth_user;
    $pasien = Pasien::find($pasienId);

    if (!$pasien) {
        return response()->json(['error' => 'Pasien tidak ditemukan.'], 404);
    }

    try {
        $persalinan = $this->bidanService->mulaiPersalinan($bidan, $pasien);
        return response()->json([
            'message' => 'Persalinan dimulai untuk pasien ini.',
            'persalinan' => $persalinan
        ], 201);
    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    }
}


    public function kirimPesan(Request $request, $pasienId)
    {
        $validator = Validator::make($request->all(), [
            'isiPesan' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bidan = $request->auth_user;
        $pasien = Pasien::find($pasienId);

        if (!$pasien) {
            return response()->json(['error' => 'Pasien tidak ditemukan.'], 404);
        }

        try {
            $pesan = $this->bidanService->kirimPesan($bidan, $pasien, $request->isiPesan);

            return response()->json([
                'message' => 'Pesan berhasil dikirim.',
                'pesan' => $pesan
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }
}
