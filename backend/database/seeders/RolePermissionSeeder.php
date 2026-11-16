<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'dashboard.view',

            // Pengguna
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Layanan
            'services.view',
            'services.create',
            'services.update',
            'services.delete',

            // Waktu layanan
            'service-times.view',
            'service-times.create',
            'service-times.update',
            'service-times.delete',

            // Pesanan
            'orders.view',
            'orders.create',
            'orders.update',
            'orders.delete',
            'orders.update-status',

            // Pembayaran
            'payments.view',
            'payments.create',

            // Proses laundry
            'laundry.view',
            'laundry.update',

            // Antar jemput
            'delivery.view',
            'delivery.assign',
            'delivery.update',

            // Laporan
            'reports.view',

            // Pengaturan
            'settings.manage',
            'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $allPermissions = Permission::where('guard_name', 'web')->get();

        // OWNER: Memiliki seluruh akses.
        $owner = Role::firstOrCreate([
            'name' => 'owner',
            'guard_name' => 'web',
        ]);

        $owner->syncPermissions($allPermissions);

        // ADMIN: Mengelola operasional dan pengguna.
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions([
            'dashboard.view',

            'users.view',
            'users.create',
            'users.update',

            'services.view',
            'services.create',
            'services.update',
            'services.delete',

            'service-times.view',
            'service-times.create',
            'service-times.update',
            'service-times.delete',

            'orders.view',
            'orders.create',
            'orders.update',
            'orders.delete',
            'orders.update-status',

            'payments.view',
            'payments.create',

            'laundry.view',
            'laundry.update',

            'delivery.view',
            'delivery.assign',
            'delivery.update',

            'reports.view',
        ]);

        // KARYAWAN: Mengelola pesanan dan proses laundry.
        $karyawan = Role::firstOrCreate([
            'name' => 'karyawan',
            'guard_name' => 'web',
        ]);

        $karyawan->syncPermissions([
            'dashboard.view',
            'services.view',
            'service-times.view',

            'orders.view',
            'orders.create',
            'orders.update',
            'orders.update-status',

            'payments.view',
            'payments.create',

            'laundry.view',
            'laundry.update',

            'delivery.view',
        ]);

        // KURIR: Mengakses tugas antar jemput.
        $kurir = Role::firstOrCreate([
            'name' => 'kurir',
            'guard_name' => 'web',
        ]);

        $kurir->syncPermissions([
            'dashboard.view',
            'delivery.view',
            'delivery.update',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
