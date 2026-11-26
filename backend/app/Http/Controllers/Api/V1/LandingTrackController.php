<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LandingTrackController extends Controller
{
    public function show(Request $request, string $kode_pesanan): JsonResponse
    {
        $kode = Str::upper(trim($kode_pesanan));
        $order = Pesanan::with(['detailPesanan', 'riwayatStatus' => fn ($query) => $query->latest('tanggal_perubahan')])
            ->where('kode_pesanan', $kode)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Nomor nota tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nomor_nota' => $order->kode_pesanan,
                'nama_pelanggan' => $order->nama_pelanggan,
                'status' => $order->status_pesanan,
                'estimasi_selesai' => $order->estimasi_selesai,
                'layanan' => $order->detailPesanan->map(fn ($detail) => [
                    'nama' => $detail->nama_layanan,
                    'jumlah' => (float) $detail->jumlah,
                    'satuan' => $detail->satuan,
                    'subtotal' => (float) $detail->subtotal,
                ]),
                'subtotal' => (float) $order->subtotal,
                'riwayat_status' => $order->riwayatStatus->map(fn ($history) => [
                    'status' => $history->status_selanjutnya,
                    'waktu' => $history->tanggal_perubahan,
                ]),
            ],
        ]);
    }
}
