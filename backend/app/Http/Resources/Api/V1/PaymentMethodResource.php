<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_metode_pembayaran,
            'nama_metode' => $this->nama_metode,
            'kode' => $this->kode,
            'deskripsi' => $this->deskripsi,
            'is_active' => $this->is_active,
            'amount' => $this->whenLoaded(
                'pembayaran',
                fn () => (float) $this->pembayaran->sum('jumlah_bayar'),
                0,
            ),
            'transactions' => $this->whenLoaded(
                'pembayaran',
                fn () => $this->pembayaran->count(),
                0,
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}