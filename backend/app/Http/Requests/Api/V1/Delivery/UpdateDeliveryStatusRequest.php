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
                    'menunggu_verifikasi',
                    'menunggu',
                    'ditugaskan',
                    'menuju_lokasi',
                    'menuju_laundry',
                    'pakaian_sampai_laundry',
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
