<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MetodePembayaranSeeder extends Seeder
{
    public function run(): void
    {
        $metodePembayaran = [
            [
                'kode' => 'tunai',
                'nama_metode' => 'Tunai',
                'deskripsi' => 'Pembayaran secara langsung menggunakan uang tunai.',
            ],
            [
                'kode' => 'transfer_bank',
                'nama_metode' => 'Transfer Bank',
                'deskripsi' => 'Pembayaran melalui transfer rekening bank.',
            ],
            [
                'kode' => 'qris',
                'nama_metode' => 'QRIS',
                'deskripsi' => 'Pembayaran menggunakan kode QRIS.',
            ],
        ];

        foreach ($metodePembayaran as $metode) {
            DB::table('metode_pembayaran')->updateOrInsert(
                ['kode' => $metode['kode']],
                [
                    'nama_metode' => $metode['nama_metode'],
                    'deskripsi' => $metode['deskripsi'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
