<?php

namespace App\Http\Requests\Api\V1\Delivery;

use App\Models\AntarJemput;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDeliveryTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_pesanan' => [
                'required',
                'integer',
                Rule::exists('pesanan', 'id_pesanan'),
            ],
            'jenis_antar_jemput' => [
                'required',
                Rule::in(['penjemputan', 'pengantaran']),
            ],
            'id_kurir' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'alamat_tujuan' => ['required', 'string'],
            'nama_penerima' => ['nullable', 'string', 'max:150'],
            'no_hp_penerima' => ['nullable', 'string', 'max:20'],
            'jadwal' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $jenis = $this->input('jenis_antar_jemput');

                if (
                    $this->filled('id_pesanan')
                    && in_array($jenis, ['penjemputan', 'pengantaran'], true)
                    && AntarJemput::where('id_pesanan', $this->input('id_pesanan'))
                        ->where('jenis_antar_jemput', $jenis)
                        ->exists()
                ) {
                    $validator->errors()->add(
                        'jenis_antar_jemput',
                        'Pesanan ini sudah memiliki tugas ' . $jenis . '.'
                    );
                }

                if (
                    $this->filled('id_kurir')
                    && !User::role('kurir')->whereKey($this->input('id_kurir'))->exists()
                ) {
                    $validator->errors()->add(
                        'id_kurir',
                        'Pengguna yang dipilih bukan kurir.'
                    );
                }
            },
        ];
    }
}
