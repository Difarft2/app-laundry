<?php

namespace Database\Seeders;

use App\Models\Layanan;
use App\Models\WaktuLayanan;
use Illuminate\Database\Seeder;

class LayananSeeder extends Seeder
{
    public function run(): void
    {
        $reguler = WaktuLayanan::where('nama_waktu', 'Reguler')
            ->firstOrFail();

        $express1Hari = WaktuLayanan::where('nama_waktu', 'Express 1 Hari')
            ->firstOrFail();

        $express4Jam = WaktuLayanan::where('nama_waktu', 'Express 4 Jam')
            ->firstOrFail();

        $data = [
            // ==========================================
            // LAYANAN KILOAN
            // ==========================================

            [
                'nama_layanan' => 'Cuci Setrika',
                'id_waktu_layanan' => $reguler->id_waktu_layanan,
                'jenis_layanan' => 'kiloan',
                'jenis_finishing' => 'setrika',
                'harga' => 7000,
                'deskripsi' => 'Cuci, keringkan, dan setrika. Estimasi 2–3 hari.',
            ],
            [
                'nama_layanan' => 'Cuci Setrika',
                'id_waktu_layanan' => $express1Hari->id_waktu_layanan,
                'jenis_layanan' => 'kiloan',
                'jenis_finishing' => 'setrika',
                'harga' => 9000,
                'deskripsi' => 'Cuci, keringkan, dan setrika. Estimasi 1 hari.',
            ],
            [
                'nama_layanan' => 'Cuci Setrika',
                'id_waktu_layanan' => $express4Jam->id_waktu_layanan,
                'jenis_layanan' => 'kiloan',
                'jenis_finishing' => 'setrika',
                'harga' => 15000,
                'deskripsi' => 'Cuci, keringkan, dan setrika. Estimasi 4 jam.',
            ],
            [
                'nama_layanan' => 'Cuci Kering',
                'id_waktu_layanan' => $reguler->id_waktu_layanan,
                'jenis_layanan' => 'kiloan',
                'jenis_finishing' => 'lipat',
                'harga' => 5000,
                'deskripsi' => 'Cuci, keringkan, kemudian dilipat tanpa disetrika.',
            ],
            [
                'nama_layanan' => 'Cuci Kering',
                'id_waktu_layanan' => $express1Hari->id_waktu_layanan,
                'jenis_layanan' => 'kiloan',
                'jenis_finishing' => 'lipat',
                'harga' => 7000,
                'deskripsi' => 'Cuci, keringkan, kemudian dilipat tanpa disetrika. Estimasi 1 hari.',
            ],
            [
                'nama_layanan' => 'Cuci Basah',
                'id_waktu_layanan' => null,
                'jenis_layanan' => 'kiloan',
                'jenis_finishing' => 'lipat',
                'harga' => 2000,
                'deskripsi' => 'Cuci kemudian langsung masuk finishing lipat. Minimal 5 kg.',
            ],
            [
                'nama_layanan' => 'Setrika',
                'id_waktu_layanan' => $reguler->id_waktu_layanan,
                'jenis_layanan' => 'kiloan',
                'jenis_finishing' => 'setrika',
                'harga' => 6000,
                'deskripsi' => 'Layanan setrika reguler. Estimasi 2–3 hari.',
            ],
            [
                'nama_layanan' => 'Setrika',
                'id_waktu_layanan' => $express1Hari->id_waktu_layanan,
                'jenis_layanan' => 'kiloan',
                'jenis_finishing' => 'setrika',
                'harga' => 8000,
                'deskripsi' => 'Layanan setrika express. Estimasi 1 hari.',
            ],
            [
                'nama_layanan' => 'Kering Saja',
                'id_waktu_layanan' => null,
                'jenis_layanan' => 'kiloan',
                'jenis_finishing' => 'lipat',
                'harga' => 3000,
                'deskripsi' => 'Pengeringan kemudian dilipat tanpa disetrika. Minimal 5 kg.',
            ],

            // ==========================================
            // LAYANAN SATUAN
            // ==========================================

            [
                'nama_layanan' => 'Bedcover Tebal',
                'id_waktu_layanan' => null,
                'jenis_layanan' => 'satuan',
                'jenis_finishing' => 'lipat',
                'harga' => 25000,
                'deskripsi' => 'Layanan bedcover ukuran tebal.',
            ],
            [
                'nama_layanan' => 'Bedcover Tipis 1',
                'id_waktu_layanan' => null,
                'jenis_layanan' => 'satuan',
                'jenis_finishing' => 'lipat',
                'harga' => 20000,
                'deskripsi' => 'Layanan bedcover tipis kategori 1.',
            ],
            [
                'nama_layanan' => 'Bedcover Tipis 2',
                'id_waktu_layanan' => null,
                'jenis_layanan' => 'satuan',
                'jenis_finishing' => 'lipat',
                'harga' => 15000,
                'deskripsi' => 'Layanan bedcover tipis kategori 2.',
            ],
            [
                'nama_layanan' => 'Boneka Besar',
                'id_waktu_layanan' => null,
                'jenis_layanan' => 'satuan',
                'jenis_finishing' => 'lipat',
                'harga' => 25000,
                'deskripsi' => 'Layanan pencucian boneka ukuran besar.',
            ],
            [
                'nama_layanan' => 'Boneka Sedang',
                'id_waktu_layanan' => null,
                'jenis_layanan' => 'satuan',
                'jenis_finishing' => 'lipat',
                'harga' => 20000,
                'deskripsi' => 'Layanan pencucian boneka ukuran sedang.',
            ],
            [
                'nama_layanan' => 'Boneka Kecil',
                'id_waktu_layanan' => null,
                'jenis_layanan' => 'satuan',
                'jenis_finishing' => 'lipat',
                'harga' => 10000,
                'deskripsi' => 'Layanan pencucian boneka ukuran kecil.',
            ],
            [
                'nama_layanan' => 'Jaket Tebal',
                'id_waktu_layanan' => null,
                'jenis_layanan' => 'satuan',
                'jenis_finishing' => 'lipat',
                'harga' => 20000,
                'deskripsi' => 'Layanan pencucian jaket tebal.',
            ],
            [
                'nama_layanan' => 'Jaket Tipis',
                'id_waktu_layanan' => null,
                'jenis_layanan' => 'satuan',
                'jenis_finishing' => 'lipat',
                'harga' => 10000,
                'deskripsi' => 'Layanan pencucian jaket tipis.',
            ],
        ];

        foreach ($data as $item) {
            Layanan::updateOrCreate(
                [
                    'nama_layanan' => $item['nama_layanan'],
                    'id_waktu_layanan' => $item['id_waktu_layanan'],
                    'jenis_layanan' => $item['jenis_layanan'],
                ],
                [
                    'jenis_finishing' => $item['jenis_finishing'],
                    'harga' => $item['harga'],
                    'deskripsi' => $item['deskripsi'],
                    'is_active' => true,
                ]
            );
        }
    }
}
