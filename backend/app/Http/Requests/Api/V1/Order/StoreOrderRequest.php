<?php

namespace App\Http\Requests\Api\V1\Order;

use App\Models\Layanan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_pelanggan' => ['required', 'string', 'max:150'],
            'no_hp' => ['required', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'tanggal_masuk' => ['required', 'date'],
            'diskon' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],

            'detail_pesanan' => ['required', 'array', 'min:1'],
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
