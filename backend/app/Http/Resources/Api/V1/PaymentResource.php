<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_pembayaran' => $this->id_pembayaran,
            'kode_pembayaran' => $this->kode_pembayaran,

            'id_pesanan' => $this->id_pesanan,
            'kode_pesanan' => $this->whenLoaded(
                'pesanan',
                fn () => $this->pesanan?->kode_pesanan
            ),
            'nama_pelanggan' => $this->whenLoaded(
                'pesanan',
                fn () => $this->pesanan?->nama_pelanggan
            ),

            'id_metode_pembayaran' => $this->id_metode_pembayaran,
            'nama_metode' => $this->whenLoaded(
                'metodePembayaran',
                fn () => $this->metodePembayaran?->nama_metode
            ),
            'kode_metode' => $this->whenLoaded(
                'metodePembayaran',
                fn () => $this->metodePembayaran?->kode
            ),

            'id_user' => $this->id_user,
            'nama_user' => $this->whenLoaded('user', fn () => $this->user?->name),

            'jumlah_bayar' => (float) $this->jumlah_bayar,
            'jenis_pembayaran' => $this->jenis_pembayaran,
            'status_pembayaran' => $this->status_pembayaran,
            'tanggal_pembayaran' => $this->tanggal_pembayaran,
            'catatan' => $this->catatan,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
