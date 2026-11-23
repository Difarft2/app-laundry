<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_pesanan' => $this->id_pesanan,
            'kode_pesanan' => $this->kode_pesanan,

            'pelanggan' => [
                'nama' => $this->nama_pelanggan,
                'no_hp' => $this->no_hp,
                'alamat' => $this->alamat,
            ],

            'tanggal_masuk' => $this->tanggal_masuk,
            'estimasi_selesai' => $this->estimasi_selesai,
            'tanggal_selesai' => $this->tanggal_selesai,

            'status_pesanan' => $this->status_pesanan,

            'subtotal' => (float) $this->subtotal,
            'diskon' => (float) $this->diskon,
            'total_harga' => (float) $this->total_harga,

            'total_terbayar' => (float) $this->pembayaran
                ->where('status_pembayaran', 'berhasil')
                ->sum('jumlah_bayar'),

            'sisa_pembayaran' => max(
                0,
                (float) $this->total_harga - (float) $this->pembayaran
                    ->where('status_pembayaran', 'berhasil')
                    ->sum('jumlah_bayar')
            ),

            'catatan' => $this->catatan,

            'detail_pesanan' => $this->whenLoaded(
                'detailPesanan',
                fn () => $this->detailPesanan->map(fn ($detail) => [
                    'id_detail_pesanan' => $detail->id_detail_pesanan,
                    'id_layanan' => $detail->id_layanan,
                    'nama_layanan' => $detail->nama_layanan,
                    'harga_satuan' => (float) $detail->harga_satuan,
                    'jumlah' => (float) $detail->jumlah,
                    'satuan' => $detail->satuan,
                    'subtotal' => (float) $detail->subtotal,
                    'catatan' => $detail->catatan,

                    'proses_laundry' => $detail->prosesLaundry ? [
                        'id_proses_laundry' => $detail->prosesLaundry->id_proses_laundry,
                        'status_proses' => $detail->prosesLaundry->status_proses,
                        'mulai_proses' => $detail->prosesLaundry->mulai_proses,
                        'selesai_proses' => $detail->prosesLaundry->selesai_proses,
                    ] : null,
                ])
            ),

            'riwayat_status' => $this->whenLoaded(
                'riwayatStatus',
                fn () => $this->riwayatStatus->map(fn ($riwayat) => [
                    'status_sebelumnya' => $riwayat->status_sebelumnya,
                    'status_selanjutnya' => $riwayat->status_selanjutnya,
                    'keterangan' => $riwayat->keterangan,
                    'tanggal_perubahan' => $riwayat->tanggal_perubahan,
                    'user' => $riwayat->user?->name,
                ])
            ),

            'antar_jemput' => $this->whenLoaded(
                'antarJemput',
                fn () => $this->antarJemput->map(fn ($task) => [
                    'id_antar_jemput' => $task->id_antar_jemput,
                    'jenis_antar_jemput' => $task->jenis_antar_jemput,
                    'status_antar_jemput' => $task->status_antar_jemput,
                    'jadwal' => $task->jadwal,
                    'alamat_tujuan' => $task->alamat_tujuan,
                    'kurir' => $task->kurir?->name,
                ])
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
