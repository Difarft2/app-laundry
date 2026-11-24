<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    public const DEFAULTS = [
        'nama_app' => 'Pemuda Laundry',
        'versi_app' => '1.0.0',
        'logo_path' => null,
        'nomor_laundry' => '081217931918',
    ];

    protected $table = 'app_settings';

    protected $primaryKey = 'id_setting';

    protected $fillable = [
        'nama_app',
        'versi_app',
        'logo_path',
        'nomor_laundry',
    ];

    protected function casts(): array
    {
        return [];
    }
}
