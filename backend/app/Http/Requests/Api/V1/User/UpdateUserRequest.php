<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'no_hp' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],
            'roles' => [
                'sometimes',
                'required',
                'array',
                'min:1',
            ],
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
