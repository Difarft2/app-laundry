<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'finance.manage',
            'guard_name' => 'web',
        ]);

        $owner = Role::firstOrCreate([
            'name' => 'owner',
            'guard_name' => 'web',
        ]);

        $owner->givePermissionTo($permission);
    }

    public function down(): void
    {
        $owner = Role::findByName('owner', 'web');
        $permission = Permission::findByName('finance.manage', 'web');

        if ($owner && $permission) {
            $owner->revokePermissionTo($permission);
        }

        DB::table('permissions')->where('name', 'finance.manage')->delete();
    }
};
