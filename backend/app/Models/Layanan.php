<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Layanan extends Model
{
    use HasFactory;

    protected $table = 'layanan';

    protected $primaryKey = 'id_layanan';

    protected $fillable = [
        'id_waktu_layanan',
        'nama_layanan',
        'jenis_layanan',
        'jenis_finishing',
        'harga',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function waktuLayanan(): BelongsTo
    {
        return $this->belongsTo(
            WaktuLayanan::class,
            'id_waktu_layanan',
            'id_waktu_layanan'
        );
    }

    public function detailPesanan(): HasMany
    {
        return $this->hasMany(
            DetailPesanan::class,
            'id_layanan',
            'id_layanan'
        );
    }
}
