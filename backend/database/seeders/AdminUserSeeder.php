<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun demo untuk setiap role.
 *
 * Password sengaja dibuat sederhana karena hanya dipakai untuk testing lokal.
 * Jangan gunakan kredensial ini di lingkungan produksi.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Owner Laundry',
                'email' => 'owner@owner.com',
                'no_hp' => '081200000001',
                'password' => 'owner123',
                'roles' => ['owner'],
            ],
            [
                'name' => 'Administrator',
                'email' => 'admin@admin.com',
                'no_hp' => '081200000002',
                'password' => 'admin123',
                'roles' => ['admin'],
            ],
            [
                'name' => 'Karyawan Laundry',
                'email' => 'karyawan@karyawan.com',
                'no_hp' => '081200000003',
                'password' => 'karyawan123',
                'roles' => ['karyawan'],
            ],
            [
                'name' => 'Kurir Laundry',
                'email' => 'kurir@kurir.com',
                'no_hp' => '081200000004',
                'password' => 'kurir123',
                'roles' => ['kurir'],
            ],
        ];

        foreach ($accounts as $account) {
            $roles = $account['roles'];
            unset($account['roles']);

            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    ...$account,
                    'password' => Hash::make($account['password']),
                ]
            );

            $user->syncRoles($roles);
        }
    }
}
