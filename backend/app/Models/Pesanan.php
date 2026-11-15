<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pesanan extends Model
{
    use HasFactory;

    protected $table = 'pesanan';

    protected $primaryKey = 'id_pesanan';

    protected $fillable = [
        'id_user',
        'kode_pesanan',
        'nama_pelanggan',
        'no_hp',
        'alamat',
        'tanggal_masuk',
        'estimasi_selesai',
        'tanggal_selesai',
        'status_pesanan',
        'subtotal',
        'diskon',
        'total_harga',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'datetime',
            'estimasi_selesai' => 'datetime',
            'tanggal_selesai' => 'datetime',
            'subtotal' => 'decimal:2',
            'diskon' => 'decimal:2',
            'total_harga' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function detailPesanan(): HasMany
    {
        return $this->hasMany(
            DetailPesanan::class,
            'id_pesanan',
            'id_pesanan'
        );
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(
            Pembayaran::class,
            'id_pesanan',
            'id_pesanan'
        );
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(
            RiwayatStatusPesanan::class,
            'id_pesanan',
            'id_pesanan'
        );
    }

    public function antarJemput(): HasMany
    {
        return $this->hasMany(
            AntarJemput::class,
            'id_pesanan',
            'id_pesanan'
        );
    }
}
