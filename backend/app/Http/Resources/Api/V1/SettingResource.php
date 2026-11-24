<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_setting' => $this->id_setting,
            'nama_app' => $this->nama_app,
            'versi_app' => $this->versi_app,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path ? url($this->logo_path) : null,
            'nomor_laundry' => $this->nomor_laundry,
            'updated_at' => $this->updated_at,
        ];
    }
}
