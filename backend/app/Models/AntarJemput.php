<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AntarJemput extends Model
{
    use HasFactory;

    protected $table = 'antar_jemput';

    protected $primaryKey = 'id_antar_jemput';

    protected $fillable = [
        'id_pesanan',
        'id_kurir',
        'jenis_antar_jemput',
        'status_antar_jemput',
        'alamat_tujuan',
        'nama_penerima',
        'no_hp_penerima',
        'jadwal',
        'tanggal_selesai',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jadwal' => 'datetime',
            'tanggal_selesai' => 'datetime',
        ];
    }

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(
            Pesanan::class,
            'id_pesanan',
            'id_pesanan'
        );
    }

    public function kurir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kurir');
    }

    public function riwayatAntarJemput(): HasMany
    {
        return $this->hasMany(
            RiwayatAntarJemput::class,
            'id_antar_jemput',
            'id_antar_jemput'
        );
    }
}
