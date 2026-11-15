<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $primaryKey = 'id_pembayaran';

    protected $fillable = [
        'id_pesanan',
        'id_metode_pembayaran',
        'id_user',
        'kode_pembayaran',
        'jumlah_bayar',
        'jenis_pembayaran',
        'status_pembayaran',
        'tanggal_pembayaran',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_bayar' => 'decimal:2',
            'tanggal_pembayaran' => 'datetime',
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

    public function metodePembayaran(): BelongsTo
    {
        return $this->belongsTo(
            MetodePembayaran::class,
            'id_metode_pembayaran',
            'id_metode_pembayaran'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
