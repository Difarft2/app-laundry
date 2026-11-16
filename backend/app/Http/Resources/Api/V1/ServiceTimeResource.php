<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceTimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_waktu_layanan,
            'nama_waktu' => $this->nama_waktu,
            'estimasi_min_jam' => $this->estimasi_min_jam,
            'estimasi_max_jam' => $this->estimasi_max_jam,
            'deskripsi' => $this->deskripsi,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
