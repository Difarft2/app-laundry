<?php

namespace App\Http\Requests\Api\V1\Order;

use App\Models\Layanan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_pelanggan' => ['sometimes', 'required', 'string', 'max:150'],
            'no_hp' => ['sometimes', 'required', 'string', 'max:20'],
            'alamat' => ['sometimes', 'nullable', 'string'],
            'tanggal_masuk' => ['sometimes', 'required', 'date'],
            'diskon' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'catatan' => ['sometimes', 'nullable', 'string'],

            'detail_pesanan' => ['sometimes', 'required', 'array', 'min:1'],
            'detail_pesanan.*.id_layanan' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('layanan', 'id_layanan')
                    ->where('is_active', true),
            ],
            'detail_pesanan.*.jumlah' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'detail_pesanan.*.catatan' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->has('detail_pesanan')) {
                    return;
                }

                foreach ($this->input('detail_pesanan', []) as $index => $detail) {
                    $layanan = Layanan::find($detail['id_layanan'] ?? null);
                    $jumlah = $detail['jumlah'] ?? null;

                    if (!$layanan || !is_numeric($jumlah) || $jumlah <= 0) {
                        continue;
                    }

                    if (
                        $layanan->jenis_layanan === 'satuan'
                        && (float) $jumlah != (int) $jumlah
                    ) {
                        $validator->errors()->add(
                            "detail_pesanan.$index.jumlah",
                            'Jumlah layanan satuan harus berupa bilangan bulat.'
                        );
                    }

                    if (
                        in_array($layanan->nama_layanan, ['Cuci Basah', 'Kering Saja'])
                        && (float) $jumlah < 5
                    ) {
                        $validator->errors()->add(
                            "detail_pesanan.$index.jumlah",
                            'Jumlah minimal layanan ini adalah 5 kg.'
                        );
                    }
                }
            },
        ];
    }
}
