<?php

namespace App\Services;

use App\Models\Persalinan;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PersalinanService
{
    public function create(array $data): Persalinan
    {
        // Validasi sederhana
        if (empty($data['pasien_no_reg'])) {
            throw ValidationException::withMessages([
                'pasien_no_reg' => 'Pasien tidak ditemukan atau belum dipilih.',
            ]);
        }

        return Persalinan::create([
            'id' => Str::uuid()->toString(),
            'pasien_no_reg' => $data['pasien_no_reg'],
            'tanggal_jam_rawat' => $data['tanggal_jam_rawat'] ?? now(),
            'tanggal_jam_mules' => $data['tanggal_jam_mules'] ?? null,
            'ketuban_pecah' => $data['ketuban_pecah'] ?? false,
            'status' => $data['status'] ?? 'tidak_aktif',
            'partograf_id' => $data['partograf_id'] ?? null,
        ]);
    }

    public function ubahStatus(Persalinan $persalinan, string $status)
    {
        return $persalinan->ubahStatus($status);
    }

    public function listByPasien($pasienNoReg)
    {
        return Persalinan::where('pasien_no_reg', $pasienNoReg)
            ->orderByDesc('tanggal_jam_rawat')
            ->get();
    }
}
