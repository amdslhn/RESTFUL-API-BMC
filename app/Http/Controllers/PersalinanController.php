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

        // Kalau pasien login
        if (isset($user->no_reg)) {
            $persalinan = $this->service->listByPasien($user->no_reg);
        } else {
            // Kalau bidan login
            $persalinan = Persalinan::with('pasien')->get();
        }

        return response()->json($persalinan);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pasien_no_reg' => 'required',
            'tanggal_jam_rawat' => 'nullable|date',
            'tanggal_jam_mules' => 'nullable|date',
            'ketuban_pecah' => 'nullable|boolean',
            'status' => 'nullable|string',
            'partograf_id' => 'nullable|string',
        ]);

        $persalinan = $this->service->create($validated);
        return response()->json(['message' => 'Persalinan berhasil dibuat', 'data' => $persalinan]);
    }

    public function ubahStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string']);
        $persalinan = Persalinan::findOrFail($id);
        $updated = $this->service->ubahStatus($persalinan, $request->status);

        return response()->json(['message' => 'Status berhasil diubah', 'data' => $updated]);
    }
}
