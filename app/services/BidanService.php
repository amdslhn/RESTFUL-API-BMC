<?php

namespace App\Services;

use App\Models\Bidan;
use App\Models\Pasien;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BidanService
{
    /**
     * 🔹 Login Bidan
     */
     public function login(array $credentials)
    {
        $bidan = Bidan::where('username', $credentials['username'])->first();
        if ($bidan && Hash::check($credentials['password'], $bidan->password)) {
            return $bidan;
        }

        return null;
    }

    /**
     * 🔹 Bidan membuat pasien baru.
     */
    public function createPasien(array $data, string $bidanId): Pasien
    {
        // Cek duplikasi no_reg atau username
        if (Pasien::where('no_reg', $data['no_reg'])->exists()) {
            throw ValidationException::withMessages([
                'no_reg' => 'Nomor registrasi sudah terdaftar.',
            ]);
        }

        if (Pasien::where('username', $data['username'])->exists()) {
            throw ValidationException::withMessages([
                'username' => 'Username sudah terdaftar.',
            ]);
        }

        // Buat pasien baru
        return Pasien::create([
            'no_reg' => $data['no_reg'],
            'username' => $data['username'],
            'nama' => $data['nama'],
            'password' => Hash::make($data['password']),
            'alamat' => $data['alamat'],
            'umur' => $data['umur'],
            'gravida' => $data['gravida'],
            'paritas' => $data['paritas'],
            'abortus' => $data['abortus'],
            'bidan_id' => $bidanId, // dikaitkan ke bidan yang sedang login
        ]);
    }
    public function lihatDaftarPasien(Bidan $bidan)
    {
        return $bidan->lihatDaftarPasien();
    }
}
