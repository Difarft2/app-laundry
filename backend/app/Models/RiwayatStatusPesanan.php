<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatusPesanan extends Model
{
    use HasFactory;

    protected $table = 'riwayat_status_pesanan';

    protected $primaryKey = 'id_riwayat_status_pesanan';

    protected $fillable = [
        'id_pesanan',
        'id_user',
        'status_sebelumnya',
        'status_selanjutnya',
        'keterangan',
        'tanggal_perubahan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_perubahan' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
