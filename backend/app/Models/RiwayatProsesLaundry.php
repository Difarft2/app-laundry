<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatProsesLaundry extends Model
{
    use HasFactory;

    protected $table = 'riwayat_proses_laundry';

    protected $primaryKey = 'id_riwayat_proses_laundry';

    protected $fillable = [
        'id_proses_laundry',
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

    public function prosesLaundry(): BelongsTo
    {
        return $this->belongsTo(
            ProsesLaundry::class,
            'id_proses_laundry',
            'id_proses_laundry'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
