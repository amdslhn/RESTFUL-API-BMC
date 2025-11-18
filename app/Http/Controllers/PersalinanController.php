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
            'status' => 'required|string|in:aktif,tidak_aktif,selesai',
        ]);

        $persalinan = Persalinan::findOrFail($id);

        // Check if the status is 'aktif' and require all fields to be filled
        if ($request->status == 'aktif') {
            $request->validate([
                'tanggal_jam_rawat' => 'required|date',
                'tanggal_jam_mules' => 'required|date',
                'ketuban_pecah' => 'required|boolean',
                'tanggal_jam_ketuban_pecah' => 'date',
            ]);
        }

        // Update status using the service method
        $updated = $this->service->ubahStatus($persalinan, $request->status);

        return response()->json([
            'message' => 'Status persalinan berhasil diubah',
            'data' => $updated
        ]);
    }

}
