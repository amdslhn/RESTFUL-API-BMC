<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pasien;
use App\Services\PasienService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PasienController extends Controller
{
    protected $pasienService;

    public function __construct(PasienService $pasienService)
    {
        $this->pasienService = $pasienService;
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

    $pasien = $this->pasienService->login($request->only('username','password'));

    if (!$pasien) {
        return response()->json(['error' => 'Username atau password salah'], 401);
    }

    $customClaims = [
        'sub' => (string) $pasien->no_reg,
        'role' => 'pasien',
        'username' => $pasien->username,
        'nama' => $pasien->nama,
    ];

    $token = auth('pasien')->claims($customClaims)->fromUser($pasien);
    $cookie = cookie('token', $token, 60 * 24);

    return response()->json([
        'message' => 'Login berhasil',
        'pasien' => [
            'no_reg' => $pasien->no_reg,
            'username' => $pasien->username,
            'nama' => $pasien->nama,
        ],
        'token' => $token
    ])->withCookie($cookie);
}
 public function getBidan($no_reg)
    {
        $result = $this->pasienService->getBidanByPasien($no_reg);

        $status = $result['status'];
        unset($result['status']); // hapus key status dari JSON response

        return response()->json($result, $status);
    }

    public function getPasienById($noReg)
    {
        $pasien = $this->pasienService->getPasienWithPersalinan($noReg);

        if (!$pasien) {
            return response()->json([
                'message' => 'Pasien tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Data pasien ditemukan',
            'data'    => $pasien
        ], 200);
    }

    public function lihatProgres($noReg)
    {
        $pasien = Pasien::where('no_reg', $noReg)->first();

        if (!$pasien) {
            return response()->json([
                'message' => 'Pasien tidak ditemukan'
            ], 404);
        }

        $data = $pasien->lihatProgresPersalinan();

        if (!$data) {
            return response()->json([
                'message' => 'Belum ada catatan partograf untuk pasien ini'
            ], 404);
        }

        return response()->json([
            'message' => 'Data progres persalinan ditemukan',
            'data' => $data
        ], 200);
    }

    public function darurat(Request $request)
    {
        // Ambil user pasien dari token JWT
        $pasien = auth('pasien')->user(); 

        if (!$pasien->bidan_id) {
            return response()->json(['message' => 'Anda belum memiliki bidan.'], 400);
        }

        // 1. Simpan Riwayat Darurat ke Database
        // Pastikan method ini ada di Model Pasien sesuai kode sebelumnya
        $riwayat = $pasien->kirimSinyalDarurat('PANIC_BUTTON');

        // 2. LOGIC NOTIFIKASI KE BIDAN
        $bidan = \App\Models\Bidan::find($pasien->bidan_id);

        if ($bidan && $bidan->fcm_token) {
            // Cek apakah token formatnya Expo (dimulai dengan ExponentPushToken)
            if (str_starts_with($bidan->fcm_token, 'ExponentPushToken')) {
                $this->sendToExpo(
                    $bidan->fcm_token, 
                    "⚠️ DARURAT: " . $pasien->nama, 
                    "Pasien menekan tombol panic button! Segera cek.",
                    ['type' => 'EMERGENCY']
                );
            }
        }

        return response()->json([
            'message' => 'Sinyal darurat terkirim!',
            'data' => $riwayat
        ], 201);
    }
    // Fungsi Helper kirim ke Expo
    private function sendToExpo($to, $title, $body, $data = [])
    {
        $url = 'https://exp.host/--/api/v2/push/send';

        $fields = [
            'to' => $to,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => 'emergency-alert', 
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
