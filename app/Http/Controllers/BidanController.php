<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pasien;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\BidanService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

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
        'no_reg'   => 'nullable|string|unique:pasien,no_reg',
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
    
    if ($request->filled('no_reg')) {
        $noReg = $request->no_reg;
    } else {
        $tahun = Carbon::now()->year;

        $lastPasien = Pasien::where('no_reg', 'like', $tahun.'%')
            ->orderBy('no_reg', 'desc')
            ->first();

        if ($lastPasien) {
            // ambil increment (2 digit di tengah)
            $lastIncrement = (int) substr($lastPasien->no_reg, 4, 2);
            $increment = str_pad($lastIncrement + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $increment = '01';
        }

        $noReg = $tahun . $increment . '03';
    }

    $data = $request->only([
        'nama', 'password', 'alamat', 'umur',
        'gravida', 'paritas', 'abortus'
    ]);

    $data['no_reg'] = $noReg;

    $pasien = $this->bidanService->tambahPasien($data, $bidan);

    return response()->json([
        'message' => 'Pasien berhasil didaftarkan',
        'pasien'  => $pasien
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

}
