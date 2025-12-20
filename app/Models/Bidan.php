<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Models\RiwayatDarurat;

class Bidan extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'bidan';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [ 'id', 'username', 'nama', 'password', 'fcm_token' ];
    protected $hidden = [ 'password' ];

    // --- RELASI ---
    public function pasien()
    {
        return $this->hasMany(Pasien::class, 'bidan_id', 'id');
    }
    
    // Tambahkan relasi ke KontenEdukasi
    public function kontenEdukasi()
    {
        return $this->hasMany(KontenEdukasi::class, 'bidan_id', 'id');
    }

    // --- AUTH ---
    public static function login(string $username, string $password): ?self
    {
        $bidan = self::where('username', $username)->first();
        if (!$bidan || !Hash::check($password, $bidan->password)) {
            return null;
        }
        return $bidan;
    }

    // 1. Tambah Pasien
    public function tambahPasien(array $data)
    {
        $data['bidan_id'] = $this->id;
        return $data;
    }

    public function lihatDaftarPasien()
    {
        $pasienList = $this->pasien()->with([
            'persalinan' => function($q) {
                $q->latest('tanggal_jam_rawat')
                ->limit(1)
                ->with('partograf'); 
            }
        ])->get();

        return $pasienList->map(function($pasien) {
            $persalinanTerbaru = $pasien->persalinan->first();

            return [
                'no_reg' => $pasien->no_reg,
                'nama' => $pasien->nama,
                'umur' => $pasien->umur,
                'alamat' => $pasien->alamat,
                'gravida' => $pasien->gravida,
                'paritas' => $pasien->paritas,
                'abortus' => $pasien->abortus,

                'persalinan' => $persalinanTerbaru ? [
                    'id' => $persalinanTerbaru->id,
                    'tanggal_jam_rawat' => $persalinanTerbaru->tanggal_jam_rawat,
                    'tanggal_jam_mules' => $persalinanTerbaru->tanggal_jam_mules,
                    'ketuban_pecah' => $persalinanTerbaru->ketuban_pecah,
                    'tanggal_jam_ketuban_pecah' => $persalinanTerbaru->tanggal_jam_ketuban_pecah,
                    'status' => $persalinanTerbaru->status,
                ] : null,
                'partograf_id' => $persalinanTerbaru->partograf->id ?? null,
            ];
        });
    }

    public function mulaiPersalinan(Request $request, Pasien $pasien)
    {
        if ($pasien->bidan_id !== $this->id) {
            throw ValidationException::withMessages([
                'pasien' => 'Pasien ini bukan pasien Anda.'
            ]);
        }

        // 🔹 Validasi input dari FE (updated)
        $validated = $request->validate([
            'tanggal_jam_rawat' => 'required|date',
            'tanggal_jam_mules' => 'required|date',
            'ketuban_pecah' => 'nullable|boolean',
            'tanggal_jam_ketuban_pecah' => 'required_if:ketuban_pecah,true|nullable|date',
            'tanggal_jam_waktu_bayi_lahir' => 'nullable|date',
        ]);

        // 🔹 Cek existing persalinan aktif
        $existing = Persalinan::where('pasien_no_reg', $pasien->no_reg)
            ->where('status', 'aktif')
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'persalinan' => 'Pasien ini sudah memiliki persalinan aktif.'
            ]);
        }

        // 🔹 Generate ID Persalinan
        $lastPersalinan = Persalinan::orderBy('id', 'desc')->first();
        $nextNumber = $lastPersalinan
            ? intval(preg_replace('/\D/', '', $lastPersalinan->id)) + 1
            : 1;

        $id = 'Persalinan' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // 🔹 Siapkan nilai
        $ketubanPecah = $validated['ketuban_pecah'] ?? false;
        $tglKetuban = $ketubanPecah ? $validated['tanggal_jam_ketuban_pecah'] : null;

        // 🔹 tanggal wajib dari FE
        $tanggalRawat = $validated['tanggal_jam_rawat'];
        $tanggalMules = $validated['tanggal_jam_mules'];
        $tanggalBayiLahir = $validated['tanggal_jam_waktu_bayi_lahir'] ?? null;

        // 🔹 Create persalinan
        $persalinanBaru = Persalinan::create([
            'id' => $id,
            'pasien_no_reg' => $pasien->no_reg,
            'tanggal_jam_rawat' => $tanggalRawat,
            'tanggal_jam_mules' => $tanggalMules,
            'ketuban_pecah' => $ketubanPecah,
            'tanggal_jam_ketuban_pecah' => $tglKetuban,
            'tanggal_jam_waktu_bayi_lahir' => $tanggalBayiLahir,
        ]);

        // 🔹 Generate Partograf
        $lastPartograf = Partograf::orderBy('id', 'desc')->first();

        $nextNumber = 1;
        if ($lastPartograf) {
            preg_match('/Partograf(\d{2})/', $lastPartograf->id, $matches);
            if (isset($matches[1])) {
                $nextNumber = intval($matches[1]) + 1;
            }
        }

        $partografId = 'Partograf'
            . str_pad($nextNumber, 2, '0', STR_PAD_LEFT)
            . $pasien->no_reg
            . date('y');

        $partograf = Partograf::create([
            'id' => $partografId,
            'persalinan_id' => $persalinanBaru->id,
        ]);

        return [
            'persalinan' => $persalinanBaru,
            'partograf' => $partograf
        ];
    }


    public function buatKonten(array $data): KontenEdukasi
    {
        $lastKonten = KontenEdukasi::orderBy('id', 'desc')->first();
        $nextNumber = $lastKonten ? intval(preg_replace('/\D/', '', $lastKonten->id)) + 1 : 1;
        $kontenId = 'Konten' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // 2. Create via Relasi (Otomatis isi bidan_id)
        return $this->kontenEdukasi()->create([
            'id' => $kontenId,
            'judul_konten' => $data['judul_konten'],
            'isi_konten' => $data['isi_konten'],
        ]);
    }

    public function hapusKonten(string $kontenId): bool
    {
        // 1. Cari konten milik bidan ini
        $konten = $this->kontenEdukasi()->where('id', $kontenId)->first();

        if (!$konten) {
            throw ValidationException::withMessages([
                'konten' => ['Konten tidak ditemukan atau bukan milik Anda.']
            ]);
        }

        // 2. Hapus
        return $konten->delete();
    }

    public function konfirmasiDarurat(string $id_darurat): bool
    {
        // 1. Cari data darurat berdasarkan ID
        $darurat = RiwayatDarurat::where('id', $id_darurat)
            ->where('bidan_id', $this->id) // Pastikan milik bidan ini
            ->first();

        if (!$darurat) {
            return false; // Data tidak ditemukan atau bukan milik bidan ini
        }

        // 2. Update Status (Logika diagram: mengubah status jadi RESOLVED)
        $darurat->update([
            'status' => 'RESOLVED',
            'waktu_selesai' => now()
        ]);

        return true; // Berhasil
    }

    // --- JWT ---
    public function getJWTIdentifier() { return $this->getKey(); }
    public function getJWTCustomClaims() { return []; }
}