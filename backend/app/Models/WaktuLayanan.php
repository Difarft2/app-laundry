<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaktuLayanan extends Model
{
    protected $table = 'waktu_layanan';

    protected $primaryKey = 'id_waktu_layanan';

    protected $fillable = [
        'nama_waktu',
        'estimasi_min_jam',
        'estimasi_max_jam',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'estimasi_min_jam' => 'integer',
            'estimasi_max_jam' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function layanan(): HasMany
    {
        return $this->hasMany(
            Layanan::class,
            'id_waktu_layanan',
            'id_waktu_layanan'
        );
    }
}
