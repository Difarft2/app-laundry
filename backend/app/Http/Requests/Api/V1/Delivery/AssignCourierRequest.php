<?php

namespace App\Http\Requests\Api\V1\Delivery;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_kurir' => [
                'present',
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (
                    $this->filled('id_kurir')
                    && !User::role('kurir')->whereKey($this->input('id_kurir'))->exists()
                ) {
                    $validator->errors()->add(
                        'id_kurir',
                        'Pengguna yang dipilih bukan kurir.'
                    );
                }
            },
        ];
    }
}
