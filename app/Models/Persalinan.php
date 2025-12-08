<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Persalinan extends Model
{
    use HasFactory;

    protected $table = 'persalinan';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'pasien_no_reg',
        'tanggal_jam_rawat',
        'tanggal_jam_mules',
        'ketuban_pecah',
        'tanggal_jam_ketuban_pecah',
        'status',
        'tanggal_jam_waktu_bayi_lahir',
        'berat_badan',
        'panjang_badan',
        'lingkar_dada',
        'lingkar_kepala',
        'jenis_kelamin',
    ];

    protected $casts = [
        'ketuban_pecah' => 'boolean',
        'tanggal_jam_rawat' => 'datetime',
        'tanggal_jam_mules' => 'datetime',
        'tanggal_jam_ketuban_pecah' => 'datetime',
        'tanggal_jam_waktu_bayi_lahir' => 'datetime',
        'berat_badan' => 'float',
        'panjang_badan' => 'float',
        'lingkar_dada' => 'float',
        'lingkar_kepala' => 'float',
        'jenis_kelamin' => 'string',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_no_reg', 'no_reg');
    }

    /**
     * 🔹 Method ubahStatus()
     * Memastikan status valid sesuai ENUM di DB
     */
    public function ubahStatus(string $status, ?array $dataBayi = null): Persalinan
{
    $allowed = ['aktif', 'tidak_aktif', 'selesai', 'rujukan'];

    if (!in_array($status, $allowed)) {
        throw new InvalidArgumentException("Status tidak valid.");
    }

    // UPDATE STATUS
    $this->status = $status;

    // ============================================
    // UPDATE FIELD RAWAT / MULES / KETUBAN
    // HANYA JIKA ADA DI REQUEST
    // ============================================
    $updatable = [
        'tanggal_jam_rawat',
        'tanggal_jam_mules',
        'ketuban_pecah',
        'tanggal_jam_ketuban_pecah'
    ];

    foreach ($updatable as $field) {
        if (request()->has($field)) {
            $this->{$field} = request()->input($field);
        }
    }

    // ============================================
    // STATUS SELESAI → WAJIB DATA BAYI
    // ============================================
    if ($status === 'selesai') {

        if (!$dataBayi) {
            throw new InvalidArgumentException("Data bayi wajib diisi untuk status selesai.");
        }

        $required = [
            'tanggal_jam_waktu_bayi_lahir',
            'berat_badan',
            'panjang_badan',
            'lingkar_dada',
            'lingkar_kepala',
            'jenis_kelamin',
        ];

        foreach ($required as $field) {
            if (!isset($dataBayi[$field])) {
                throw new InvalidArgumentException("Field {$field} wajib diisi.");
            }
        }

        // SIMPAN DATA BAYI
        $this->tanggal_jam_waktu_bayi_lahir = $dataBayi['tanggal_jam_waktu_bayi_lahir'];
        $this->berat_badan = $dataBayi['berat_badan'];
        $this->panjang_badan = $dataBayi['panjang_badan'];
        $this->lingkar_dada = $dataBayi['lingkar_dada'];
        $this->lingkar_kepala = $dataBayi['lingkar_kepala'];
        $this->jenis_kelamin = $dataBayi['jenis_kelamin'];
    }

    // SIMPAN
    $this->save();

    return $this;
}



    public function partograf()
{
    return $this->hasOne(Partograf::class, 'persalinan_id', 'id');
}

}
