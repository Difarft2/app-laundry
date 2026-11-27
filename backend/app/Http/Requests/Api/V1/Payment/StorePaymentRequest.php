<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_pesanan' => ['required', 'integer', 'exists:pesanan,id_pesanan'],
            'id_metode_pembayaran' => [
                'required',
                'integer',
                Rule::exists('metode_pembayaran', 'id_metode_pembayaran')
                    ->where('is_active', true),
            ],
            'jumlah_bayar' => ['required', 'numeric', 'gt:0'],
            'idempotency_key' => ['required', 'string', 'uuid'],
            'jenis_pembayaran' => [
                'required',
                'string',
                Rule::in(['dp', 'angsuran', 'pelunasan']),
            ],
            'status_pembayaran' => [
                'sometimes',
                'string',
                Rule::in(['berhasil', 'menunggu']),
            ],
            'tanggal_pembayaran' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
            'amount_received' => ['required', 'numeric', 'gte:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_metode_pembayaran.exists' => 'Metode pembayaran tidak aktif atau tidak ditemukan.',
            'jumlah_bayar.gt' => 'Jumlah bayar harus lebih dari 0.',
        ];
    }
}
