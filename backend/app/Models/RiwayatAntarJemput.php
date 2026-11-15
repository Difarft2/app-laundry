<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatAntarJemput extends Model
{
    use HasFactory;

    protected $table = 'riwayat_antar_jemput';

    protected $primaryKey = 'id_riwayat_antar_jemput';

    protected $fillable = [
        'id_antar_jemput',
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

    public function antarJemput(): BelongsTo
    {
        return $this->belongsTo(
            AntarJemput::class,
            'id_antar_jemput',
            'id_antar_jemput'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
