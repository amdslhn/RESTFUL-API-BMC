<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Bidan extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'bidan';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'username',
        'nama',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    // Relasi ke pasien (satu bidan bisa punya banyak pasien)
    public function pasien()
    {
        return $this->hasMany(Pasien::class, 'bidan_id', 'id');
    }

    // --- Metode wajib JWT ---
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function lihatDaftarPasien()
    {
        return $this->pasien()->get([
            'no_reg',
            'nama',
            'umur',
            'alamat',
            'gravida',
            'paritas',
            'abortus'
        ]);
    }
    
    public function getJWTCustomClaims()
    {
        return []; // Tidak ada tambahan claim
    }
}
