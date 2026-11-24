<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_antar_jemput' => $this->id_antar_jemput,
            'sumber' => $this->sumber,
            'jenis_antar_jemput' => $this->jenis_antar_jemput,
            'status_antar_jemput' => $this->status_antar_jemput,

            'alamat_tujuan' => $this->alamat_tujuan,
            'nama_penerima' => $this->nama_penerima,
            'no_hp_penerima' => $this->no_hp_penerima,

            'jadwal' => $this->jadwal,
            'tanggal_selesai' => $this->tanggal_selesai,
            'catatan' => $this->catatan,

            'kurir' => $this->kurir ? [
                'id' => $this->kurir->id,
                'name' => $this->kurir->name,
                'no_hp' => $this->kurir->no_hp,
            ] : null,

            'pesanan' => $this->whenLoaded(
                'pesanan',
                fn () => $this->pesanan ? [
                    'id_pesanan' => $this->pesanan->id_pesanan,
                    'kode_pesanan' => $this->pesanan->kode_pesanan,
                    'status_pesanan' => $this->pesanan->status_pesanan,
                    'total_harga' => (float) $this->pesanan->total_harga,
                    'pelanggan' => [
                        'nama' => $this->pesanan->nama_pelanggan,
                        'no_hp' => $this->pesanan->no_hp,
                        'alamat' => $this->pesanan->alamat,
                    ],
                ] : null
            ),

            'riwayat' => $this->whenLoaded(
                'riwayatAntarJemput',
                fn () => $this->riwayatAntarJemput->map(fn ($riwayat) => [
                    'status_sebelumnya' => $riwayat->status_sebelumnya,
                    'status_selanjutnya' => $riwayat->status_selanjutnya,
                    'keterangan' => $riwayat->keterangan,
                    'tanggal_perubahan' => $riwayat->tanggal_perubahan,
                    'user' => $riwayat->user?->name,
                ])
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
