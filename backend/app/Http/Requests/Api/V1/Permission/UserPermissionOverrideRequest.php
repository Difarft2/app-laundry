<?php

namespace App\Http\Requests\Api\V1\Permission;

use Illuminate\Foundation\Http\FormRequest;

class UserPermissionOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_name' => ['required', 'string', 'exists:permissions,name'],
            'status' => ['required', 'string', 'in:GRANT DENY INHERIT'],
        ];
    }

    public function messages(): array
    {
        return [
            'permission_name.required' => 'Nama permission wajib diisi.',
            'permission_name.exists' => 'Permission tidak valid.',
            'status.required' => 'Status permission wajib diisi.',
            'status.in' => 'Status permission harus GRANT, DENY, atau INHERIT.',
        ];
    }
}