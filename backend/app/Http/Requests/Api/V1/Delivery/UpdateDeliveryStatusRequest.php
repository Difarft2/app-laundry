<?php

namespace App\Http\Requests\Api\V1\Delivery;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_antar_jemput' => [
                'required',
                Rule::in([
                    'menunggu',
                    'ditugaskan',
                    'menuju_lokasi',
                    'barang_diambil',
                    'barang_diantar',
                    'selesai',
                    'gagal',
                ]),
            ],
            'keterangan' => ['nullable', 'string'],
        ];
    }
}
