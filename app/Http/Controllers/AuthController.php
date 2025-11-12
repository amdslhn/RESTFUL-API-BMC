<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PasienService;
use App\Services\BidanService;
use Illuminate\Support\Facades\Cookie;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $pasienService;
    protected $bidanService;

    public function __construct(PasienService $pasienService, BidanService $bidanService)
    {
        $this->pasienService = $pasienService;
        $this->bidanService = $bidanService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');
        $user = null;
        $userType = null;

        // 🔹 Cek login pasien
        $pasien = $this->pasienService->login($credentials);
        if ($pasien) {
            $user = $pasien;
            $userType = 'pasien';
        }

        // 🔹 Cek login bidan
        if (!$user) {
            $bidan = $this->bidanService->login($credentials);
            if ($bidan) {
                $user = $bidan;
                $userType = 'bidan';
            }
        }

        if (!$user) {
            return response()->json(['error' => 'Username atau password salah'], 401);
        }

        // 🔹 Klaim JWT
        $customClaims = $userType === 'pasien'
            ? [
                'sub' => (string) $user->no_reg, // <-- tambahkan sub secara eksplisit
                'role' => 'pasien',
                'username' => $user->username,
                'nama' => $user->nama,
            ]
            : [
                'sub' => (string) $user->id, // <-- tambahkan sub secara eksplisit
                'role' => 'bidan',
                'username' => $user->username,
                'nama' => $user->nama,
            ];

        // 🔹 Generate token hanya di controller
        $token = auth($userType)->claims($customClaims)->fromUser($user);

        // 🔹 Simpan token di cookie (1 hari)
        $cookie = cookie('token', $token, 60 * 24);

        // 🔹 Response JSON
        $responseData = [
            'message' => 'Login berhasil',
            $userType => $userType === 'pasien'
                ? [
                    'no_reg' => $user->no_reg,
                    'username' => $user->username,
                    'nama' => $user->nama
                ]
                : [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nama' => $user->nama
                ]
        ];

        return response()->json($responseData)->withCookie($cookie);
    }

    /**
     * Logout user - hapus cookie JWT.
     */
    public function logout()
    {
        $cookie = Cookie::forget('token');
        return response()->json(['message' => 'Logout berhasil'])->withCookie($cookie);
    }

}