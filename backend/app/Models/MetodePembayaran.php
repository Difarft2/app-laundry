<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodePembayaran extends Model
{
    use HasFactory;

    protected $table = 'metode_pembayaran';

    protected $primaryKey = 'id_metode_pembayaran';

    protected $fillable = [
        'nama_metode',
        'kode',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(
            Pembayaran::class,
            'id_metode_pembayaran',
            'id_metode_pembayaran'
        );
    }
}
