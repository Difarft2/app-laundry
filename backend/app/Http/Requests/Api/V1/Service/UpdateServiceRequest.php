<?php

namespace App\Http\Requests\Api\V1\Service;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_layanan' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'id_waktu_layanan' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:waktu_layanan,id_waktu_layanan',
            ],

            'jenis_layanan' => [
                'sometimes',
                'required',
                'in:kiloan,satuan',
            ],

            'jenis_finishing' => [
                'sometimes',
                'required',
                'in:lipat,setrika,tanpa_finishing',
            ],

            'harga' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
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

    public function messages(): array
    {
        return [
            'nama_layanan.required' => 'Nama layanan wajib diisi.',
            'nama_layanan.max' => 'Nama layanan maksimal 100 karakter.',
            'id_waktu_layanan.exists' => 'Waktu layanan tidak ditemukan.',
            'jenis_layanan.required' => 'Jenis layanan wajib dipilih.',
            'jenis_layanan.in' => 'Jenis layanan harus kiloan atau satuan.',
            'jenis_finishing.required' => 'Jenis finishing wajib dipilih.',
            'jenis_finishing.in' => 'Jenis finishing tidak valid.',
            'harga.required' => 'Harga layanan wajib diisi.',
            'harga.numeric' => 'Harga harus berupa angka.',
            'harga.min' => 'Harga tidak boleh kurang dari 0.',
        ];
    }
}