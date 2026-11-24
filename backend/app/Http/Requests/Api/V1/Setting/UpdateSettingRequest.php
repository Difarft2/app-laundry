<?php

namespace App\Http\Requests\Api\V1\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_app' => ['required', 'string', 'max:150'],
            'versi_app' => ['required', 'string', 'max:50'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'nomor_laundry' => [
                'required',
                'string',
                'max:30',
                'regex:/^[0-9+\-\s()]+$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_app.required' => 'Nama aplikasi wajib diisi.',
            'versi_app.required' => 'Versi aplikasi wajib diisi.',
            'nomor_laundry.required' => 'Nomor laundry wajib diisi.',
            'nomor_laundry.regex' => 'Nomor laundry hanya boleh berisi angka dan karakter telepon.',
        ];
    }
}
