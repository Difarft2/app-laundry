<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create all permissions once.
        foreach ([
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'services.view',
            'service-times.view',
            'payment-methods.view',
            'service-times.update',
            'service-times.delete',
            'payment-methods.update',
            'payment-methods.delete',
            'orders.view',
            'orders.create',
            'orders.update',
            'orders.update-status',
            'orders.delete',
            'payments.view',
            'payments.create',
            'laundry.view',
            'laundry.update',
            'delivery.view',
            'delivery.assign',
            'delivery.update',
            'reports.view',
            'settings.manage',
            'roles.manage',
            'users.approve',
            'services.create',
            'services.update',
            'services.delete',
            'service-times.create',
            'payment-methods.create',
            'finance.view',
            'finance.manage',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions([
            'dashboard.view',
            'users.view',
            'users.update',
            'users.approve',
            'services.view',
            'services.create',
            'services.update',
            'services.delete',
            'service-times.view',
            'service-times.create',
            'service-times.update',
            'service-times.delete',
            'payment-methods.view',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',
            'orders.view',
            'payments.view',
            'laundry.view',
            'delivery.view',
            'reports.view',
            'finance.view',
        ]);

        $owner = Role::firstOrCreate([
            'name' => 'owner',
            'guard_name' => 'web',
        ]);

        $owner->syncPermissions([
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'services.view',
            'service-times.view',
            'payment-methods.view',
            'orders.view',
            'orders.update-status',
            'orders.delete',
            'payments.view',
            'delivery.view',
            'delivery.assign',
            'reports.view',
            'finance.view',
            'finance.manage',
            'settings.manage',
            'roles.manage',
        ]);

        $karyawan = Role::firstOrCreate([
            'name' => 'karyawan',
            'guard_name' => 'web',
        ]);

        $karyawan->syncPermissions([
            'dashboard.view',
            'services.view',
            'service-times.view',
            'payment-methods.view',
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

        $kurir = Role::firstOrCreate([
            'name' => 'kurir',
            'guard_name' => 'web',
        ]);

        $kurir->syncPermissions([
            'dashboard.view',
            'delivery.view',
            'delivery.update',
        ]);
    }
}
