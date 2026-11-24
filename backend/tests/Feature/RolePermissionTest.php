<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user->fresh();
    }

    public function test_only_karyawan_can_create_orders(): void
    {
        $this->assertTrue(
            $this->userWithRole('karyawan')->hasPermissionTo('orders.create')
        );

        foreach (['owner', 'admin', 'kurir'] as $role) {
            $this->assertFalse(
                $this->userWithRole($role)->hasPermissionTo('orders.create'),
                "Role {$role} tidak boleh memiliki orders.create."
            );
        }
    }

    public function test_admin_only_has_read_permissions(): void
    {
        $admin = $this->userWithRole('admin');

        $readPermissions = [
            'dashboard.view',
            'users.view',
            'services.view',
            'service-times.view',
            'payment-methods.view',
            'orders.view',
            'payments.view',
            'laundry.view',
            'delivery.view',
            'reports.view',
        ];

        foreach ($readPermissions as $permission) {
            $this->assertTrue(
                $admin->hasPermissionTo($permission),
                "Admin harus memiliki {$permission}."
            );
        }

        $writePermissions = [
            'users.create',
            'users.update',
            'users.delete',
            'services.create',
            'services.update',
            'services.delete',
            'service-times.create',
            'service-times.update',
            'service-times.delete',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',
            'orders.create',
            'orders.update',
            'orders.delete',
            'orders.update-status',
            'payments.create',
            'laundry.update',
            'delivery.assign',
            'delivery.update',
            'roles.manage',
        ];

        foreach ($writePermissions as $permission) {
            $this->assertFalse(
                $admin->hasPermissionTo($permission),
                "Admin tidak boleh memiliki {$permission}."
            );
        }

        // Admin adalah satu-satunya role yang dapat mengedit pengaturan global.
        $this->assertTrue($admin->hasPermissionTo('settings.manage'));
    }

    public function test_owner_cannot_create_orders_or_manage_master_data(): void
    {
        $owner = $this->userWithRole('owner');

        $forbiddenPermissions = [
            'orders.create',
            'services.create',
            'services.update',
            'services.delete',
            'service-times.create',
            'service-times.update',
            'service-times.delete',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',
            'settings.manage',
        ];

        foreach ($forbiddenPermissions as $permission) {
            $this->assertFalse(
                $owner->hasPermissionTo($permission),
                "Owner tidak boleh memiliki {$permission}."
            );
        }

        $allowedPermissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'orders.view',
            'orders.update-status',
            'payments.view',
            'delivery.view',
            'delivery.assign',
            'reports.view',
        ];

        foreach ($allowedPermissions as $permission) {
            $this->assertTrue(
                $owner->hasPermissionTo($permission),
                "Owner harus memiliki {$permission}."
            );
        }
    }

    public function test_karyawan_can_create_orders_and_manage_master_data(): void
    {
        $karyawan = $this->userWithRole('karyawan');

        $allowedPermissions = [
            'orders.create',
            'orders.view',
            'orders.update',
            'orders.update-status',
            'services.create',
            'service-times.create',
            'payment-methods.create',
            'payments.create',
            'laundry.update',
        ];

        foreach ($allowedPermissions as $permission) {
            $this->assertTrue(
                $karyawan->hasPermissionTo($permission),
                "Karyawan harus memiliki {$permission}."
            );
        }

        $this->assertFalse($karyawan->hasPermissionTo('reports.view'));
        $this->assertFalse($karyawan->hasPermissionTo('users.view'));
        $this->assertFalse($karyawan->hasPermissionTo('settings.manage'));
    }

    public function test_kurir_only_has_delivery_access(): void
    {
        $kurir = $this->userWithRole('kurir');

        $this->assertTrue($kurir->hasPermissionTo('dashboard.view'));
        $this->assertTrue($kurir->hasPermissionTo('delivery.view'));
        $this->assertTrue($kurir->hasPermissionTo('delivery.update'));

        $forbiddenPermissions = [
            'orders.view',
            'orders.create',
            'orders.update',
            'payments.view',
            'payments.create',
            'services.view',
            'service-times.view',
            'payment-methods.view',
            'laundry.view',
            'users.view',
            'reports.view',
            'delivery.assign',
            'settings.manage',
        ];

        foreach ($forbiddenPermissions as $permission) {
            $this->assertFalse(
                $kurir->hasPermissionTo($permission),
                "Kurir tidak boleh memiliki {$permission}."
            );
        }
    }

    public function test_admin_and_owner_are_forbidden_from_creating_orders_via_api(): void
    {
        foreach (['admin', 'owner'] as $role) {
            $response = $this->actingAs($this->userWithRole($role), 'sanctum')
                ->postJson('/api/v1/orders', []);

            $response->assertForbidden();
        }
    }
}
