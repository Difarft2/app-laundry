<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProsesLaundry extends Model
{
    use HasFactory;

    protected $table = 'proses_laundry';

    protected $primaryKey = 'id_proses_laundry';

    protected $fillable = [
        'id_detail_pesanan',
        'id_user',
        'status_proses',
        'mulai_proses',
        'selesai_proses',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'mulai_proses' => 'datetime',
            'selesai_proses' => 'datetime',
        ];
    }

    public function detailPesanan(): BelongsTo
    {
        return $this->belongsTo(
            DetailPesanan::class,
            'id_detail_pesanan',
            'id_detail_pesanan'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function riwayatProses(): HasMany
    {
        return $this->hasMany(
            RiwayatProsesLaundry::class,
            'id_proses_laundry',
            'id_proses_laundry'
        );
    }
}
