<?php

namespace Database\Seeders;

use App\Models\WaktuLayanan;
use Illuminate\Database\Seeder;

class WaktuLayananSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'nama_waktu' => 'Reguler',
                'estimasi_min_jam' => 48,
                'estimasi_max_jam' => 72,
                'deskripsi' => 'Estimasi pengerjaan 2 sampai 3 hari.',
            ],
            [
                'nama_waktu' => 'Express 1 Hari',
                'estimasi_min_jam' => 24,
                'estimasi_max_jam' => 24,
                'deskripsi' => 'Estimasi pengerjaan 1 hari.',
            ],
            [
                'nama_waktu' => 'Express 4 Jam',
                'estimasi_min_jam' => 4,
                'estimasi_max_jam' => 4,
                'deskripsi' => 'Estimasi pengerjaan 4 jam.',
            ],
        ];

        foreach ($data as $item) {
            WaktuLayanan::updateOrCreate(
                ['nama_waktu' => $item['nama_waktu']],
                [
                    ...$item,
                    'is_active' => true,
                ]
            );
        }
    }
}
