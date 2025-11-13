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
     public function login(array $credentials): ?Bidan
     {
        return Bidan::login($credentials['username'], $credentials['password']);
     }


    /**
     * 🔹 Bidan membuat pasien baru.
     */
   public function createPasien(array $data, Bidan $bidan): Pasien
    {
        // Username otomatis sama dengan nama
        $username = $data['nama'];

        // Cek duplikasi no_reg dan username
        if (Pasien::where('no_reg', $data['no_reg'])->exists()) {
            throw ValidationException::withMessages([
                'no_reg' => 'Nomor registrasi sudah terdaftar.',
            ]);
        }

        if (Pasien::where('username', $username)->exists()) {
            throw ValidationException::withMessages([
                'username' => 'Username sudah terdaftar.',
            ]);
        }

        // Password default sama dengan username jika tidak diberikan
        $password = $data['password'] ?? $username;

        return Pasien::create([
            'no_reg' => $data['no_reg'],
            'username' => $username,
            'nama' => $data['nama'],
            'password' => Hash::make($password),
            'alamat' => $data['alamat'],
            'umur' => $data['umur'],
            'gravida' => $data['gravida'],
            'paritas' => $data['paritas'],
            'abortus' => $data['abortus'],
            'bidan_id' => $bidan->id
        ]);
    }

    //Lihat datapasien
    public function lihatDaftarPasien(Bidan $bidan)
    {
        return $bidan->lihatDaftarPasien();
    }

    public function mulaiPersalinan(Bidan $bidan, Pasien $pasien)
{
    return $bidan->mulaiPersalinan($pasien);
}

    public function kirimPesan(Bidan $bidan, Pasien $pasien, string $isiPesan)
    {
        return $bidan->kirimPesan($pasien, $isiPesan);
    }
}
