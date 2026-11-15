<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DetailPesanan extends Model
{
    use HasFactory;

    protected $table = 'detail_pesanan';

    protected $primaryKey = 'id_detail_pesanan';

    protected $fillable = [
        'id_pesanan',
        'id_layanan',
        'nama_layanan',
        'harga_satuan',
        'jumlah',
        'satuan',
        'subtotal',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'jumlah' => 'decimal:2',
            'subtotal' => 'decimal:2',
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

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(
            Layanan::class,
            'id_layanan',
            'id_layanan'
        );
    }

    public function prosesLaundry(): HasOne
    {
        return $this->hasOne(
            ProsesLaundry::class,
            'id_detail_pesanan',
            'id_detail_pesanan'
        );
    }
}
