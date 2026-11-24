<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        AppSetting::firstOrCreate(
            ['id_setting' => 1],
            AppSetting::DEFAULTS
        );
    }
}
