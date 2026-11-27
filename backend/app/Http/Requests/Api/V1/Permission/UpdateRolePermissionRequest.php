<?php

namespace App\Http\Requests\Api\V1\Permission;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*.name' => ['required', 'string', 'exists:permissions,name'],
            'permissions.*.status' => ['required', 'string', 'in:GRANT REVOKE DENY'],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'Daftar permission wajib diisi.',
            'permissions.array' => 'Daftar permission harus berupa array.',
            'permissions.*.name' => 'Nama permission tidak valid.',
            'permissions.*.status' => 'Status permission harus GRANT atau REVOKE.',
        ];
    }
}