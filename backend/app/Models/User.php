<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'no_hp',
    'password',
    'status',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles {
        hasPermissionTo as protected spatieHasPermissionTo;
    }

    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        $permissionName = $permission instanceof \Spatie\Permission\Contracts\Permission
            ? $permission->name
            : (string) $permission;

        $override = \Illuminate\Support\Facades\DB::table('user_permission_overrides')
            ->where('user_id', $this->getKey())
            ->where('permission_name', $permissionName)
            ->value('status');

        return match ($override) {
            'DENY' => false,
            'GRANT' => true,
            default => $this->spatieHasPermissionTo($permission, $guardName),
        };
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
