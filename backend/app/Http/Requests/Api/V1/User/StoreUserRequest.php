<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'required',
                'string',
                'distinct',
                Rule::exists('roles', 'name')
                    ->where('guard_name', 'web'),
            ],
        ];
    }
}
