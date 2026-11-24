<?php

namespace App\Http\Requests\Api\V1\Landing;

use Illuminate\Foundation\Http\FormRequest;

class StoreLandingDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'telepon' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'alamat' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'telepon.required' => 'Nomor telepon wajib diisi.',
            'telepon.regex' => 'Nomor telepon hanya boleh berisi angka dan karakter telepon.',
            'alamat.required' => 'Alamat wajib diisi.',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['nama', 'telepon', 'alamat'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }
}
