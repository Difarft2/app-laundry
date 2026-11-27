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
            'delivery.verify',
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
            // Dashboard
            'dashboard.view',

            // User Management
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Service Management
            'services.view',
            'services.create',
            'services.update',
            'services.delete',

            // Service Time Management
            'service-times.view',
            'service-times.create',
            'service-times.update',
            'service-times.delete',

            // Payment Method Management
            'payment-methods.view',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',

            // Orders
            'orders.view',

            // Payments
            'payments.view',

            // Laundry
            'laundry.view',

            // Delivery
            'delivery.view',

            // Reports & Finance
            'reports.view',
            'finance.view',
            'finance.manage',

            // Settings & Role Permission Management
            'settings.manage',
            'roles.manage',
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
            'delivery.verify',
            'reports.view',
            'finance.view',
            'finance.manage',
        ]);

        $karyawan = Role::firstOrCreate([
            'name' => 'karyawan',
            'guard_name' => 'web',
        ]);

        $karyawan->syncPermissions([
            'dashboard.view',
            'services.view',
            'services.create',
            'service-times.view',
            'service-times.create',
            'payment-methods.view',
            'payment-methods.create',
            'orders.view',
            'orders.create',
            'orders.update',
            'orders.update-status',
            'payments.view',
            'payments.create',
            'laundry.view',
            'laundry.update',
            'delivery.view',
            'delivery.verify',
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
