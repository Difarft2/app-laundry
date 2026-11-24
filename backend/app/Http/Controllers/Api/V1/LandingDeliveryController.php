<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Landing\StoreLandingDeliveryRequest;
use App\Models\AntarJemput;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LandingDeliveryController extends Controller
{
    public function store(StoreLandingDeliveryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $task = DB::transaction(function () use ($validated) {
            $task = AntarJemput::create([
                'id_pesanan' => null,
                'id_kurir' => null,
                'jenis_antar_jemput' => 'penjemputan',
                'status_antar_jemput' => 'menunggu_verifikasi',
                'sumber' => 'landing',
                'alamat_tujuan' => $validated['alamat'],
                'nama_penerima' => $validated['nama'],
                'no_hp_penerima' => $validated['telepon'],
                'catatan' => 'Permintaan penjemputan dari landing page.',
            ]);

            $task->riwayatAntarJemput()->create([
                'id_user' => null,
                'status_sebelumnya' => null,
                'status_selanjutnya' => 'menunggu_verifikasi',
                'keterangan' => 'Permintaan dibuat dari landing page.',
                'tanggal_perubahan' => now(),
            ]);

            return $task;
        });

        return response()->json([
            'success' => true,
            'message' => 'Permintaan penjemputan berhasil dikirim. Tim kami akan memverifikasi data Anda.',
            'data' => [
                'id' => $task->id_antar_jemput,
                'status' => $task->status_antar_jemput,
                'created_at' => $task->created_at,
            ],
        ], 201);
    }
}
