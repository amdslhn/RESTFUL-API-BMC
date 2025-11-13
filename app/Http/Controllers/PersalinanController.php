<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PersalinanService;
use App\Models\Persalinan;

class PersalinanController extends Controller
{
    protected $service;

    public function __construct(PersalinanService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/persalinan
     * Tampilkan daftar persalinan, otomatis filter kalau pasien login
     */
    public function index(Request $request)
    {
        $user = $request->auth_user;

        if (isset($user->no_reg)) {
            $persalinan = $this->service->listByPasien($user->no_reg);
        } else {
            $persalinan = Persalinan::with('pasien')->get();
        }

        return response()->json($persalinan);
    }


    /**
     * PUT /api/persalinan/{id}/status
     * Ubah status persalinan (aktif/tidak_aktif/selesai)
     */
    public function ubahStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:aktif,tidak_aktif,selesai'
        ]);

        $persalinan = Persalinan::findOrFail($id);
        $updated = $this->service->ubahStatus($persalinan, $request->status);

        return response()->json([
            'message' => 'Status persalinan berhasil diubah',
            'data' => $updated
        ]);
    }
}
