<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persalinan extends Model
{
    use HasFactory;

    protected $table = 'persalinan';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'pasien_no_reg',
        'tanggal_jam_rawat',
        'tanggal_jam_mules',
        'ketuban_pecah',
        'status', // string aja (aktif, tidak aktif, selesai)
        'partograf_id',
    ];

    protected $casts = [
        'ketuban_pecah' => 'boolean',
        'tanggal_jam_rawat' => 'datetime',
        'tanggal_jam_mules' => 'datetime',
    ];

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'pasien_no_reg', 'no_reg');
    }

    // 🔹 Helper: ubah status persalinan
    public function ubahStatus(string $status)
    {
        $allowed = ['aktif', 'tidak_aktif', 'selesai'];
        if (!in_array($status, $allowed)) {
            throw new \InvalidArgumentException("Status tidak valid.");
        }

        $this->status = $status;
        $this->save();

        return $this;
    }
}
