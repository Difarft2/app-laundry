<?php

namespace App\Http\Requests\Api\V1\PaymentMethod;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_metode' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:50', 'unique:metode_pembayaran,kode'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}