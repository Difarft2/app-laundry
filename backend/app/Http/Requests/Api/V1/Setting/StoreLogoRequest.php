<?php

namespace App\Http\Requests\Api\V1\Setting;

use Illuminate\Foundation\Http\FormRequest;

class StoreLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'file',
                'mimes:png,jpg,jpeg,webp,svg',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'Berkas logo wajib dipilih.',
            'logo.mimes' => 'Logo harus berformat PNG, JPG, WEBP, atau SVG.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
        ];
    }
}
