<?php

namespace App\Http\Requests\Api\V1\PaymentMethod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paymentMethod = $this->route('paymentMethod');

        return [
            'nama_metode' => ['sometimes', 'required', 'string', 'max:100'],
            'kode' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('metode_pembayaran', 'kode')
                    ->ignore($paymentMethod?->id_metode_pembayaran, 'id_metode_pembayaran'),
            ],
            'deskripsi' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}