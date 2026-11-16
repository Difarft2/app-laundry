<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_layanan,
            'nama_layanan' => $this->nama_layanan,
            'jenis_layanan' => $this->jenis_layanan,
            'jenis_finishing' => $this->jenis_finishing,
            'harga' => $this->harga,
            'deskripsi' => $this->deskripsi,
            'is_active' => $this->is_active,

            'waktu_layanan' => $this->whenLoaded(
                'waktuLayanan',
                fn () => $this->waktuLayanan ? [
                    'id' => $this->waktuLayanan->id_waktu_layanan,
                    'nama_waktu' => $this->waktuLayanan->nama_waktu,
                    'estimasi_min_jam' => $this->waktuLayanan->estimasi_min_jam,
                    'estimasi_max_jam' => $this->waktuLayanan->estimasi_max_jam,
                ] : null
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
