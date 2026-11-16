<?php

namespace App\Http\Requests\Api\V1\ServiceTime;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateServiceTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_waktu' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'estimasi_min_jam' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:65535',
            ],

            'estimasi_max_jam' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:65535',
            ],

            'deskripsi' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $serviceTime = $this->route('service_time');

            $min = $this->has('estimasi_min_jam')
                ? $this->input('estimasi_min_jam')
                : $serviceTime?->estimasi_min_jam;

            $max = $this->has('estimasi_max_jam')
                ? $this->input('estimasi_max_jam')
                : $serviceTime?->estimasi_max_jam;

            if ($min !== null && $max !== null && (int) $max < (int) $min) {
                $validator->errors()->add(
                    'estimasi_max_jam',
                    'Estimasi maksimal tidak boleh lebih kecil dari estimasi minimal.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'nama_waktu.required' => 'Nama waktu layanan wajib diisi.',
            'nama_waktu.max' => 'Nama waktu layanan maksimal 100 karakter.',
            'estimasi_min_jam.integer' => 'Estimasi minimal harus berupa angka.',
            'estimasi_min_jam.min' => 'Estimasi minimal tidak boleh kurang dari 0.',
            'estimasi_max_jam.integer' => 'Estimasi maksimal harus berupa angka.',
            'estimasi_max_jam.min' => 'Estimasi maksimal tidak boleh kurang dari 0.',
        ];
    }
}
