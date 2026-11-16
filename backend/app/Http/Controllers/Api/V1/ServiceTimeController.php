<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ServiceTime\StoreServiceTimeRequest;
use App\Http\Requests\Api\V1\ServiceTime\UpdateServiceTimeRequest;
use App\Http\Resources\Api\V1\ServiceTimeResource;
use App\Models\WaktuLayanan;
use Illuminate\Http\Request;

class ServiceTimeController extends Controller
{
    /**
     * Menampilkan daftar waktu layanan.
     */
    public function index(Request $request)
    {
        $query = WaktuLayanan::query();

        // Pencarian nama waktu layanan.
        $query->when($request->filled('search'), function ($query) use ($request) {
            $query->where(
                'nama_waktu',
                'like',
                '%' . $request->search . '%'
            );
        });

        // Filter status aktif.
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min(
            max((int) $request->input('per_page', 15), 1),
            100
        );

        $serviceTimes = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return ServiceTimeResource::collection($serviceTimes);
    }

    /**
     * Menampilkan detail waktu layanan.
     */
    public function show(WaktuLayanan $serviceTime)
    {
        return new ServiceTimeResource($serviceTime);
    }

    /**
     * Menambahkan waktu layanan baru.
     */
    public function store(StoreServiceTimeRequest $request)
    {
        $serviceTime = WaktuLayanan::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Waktu layanan berhasil ditambahkan.',
            'data' => new ServiceTimeResource($serviceTime),
        ], 201);
    }

    /**
     * Memperbarui waktu layanan.
     */
    public function update(
        UpdateServiceTimeRequest $request,
        WaktuLayanan $serviceTime
    ) {
        $serviceTime->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Waktu layanan berhasil diperbarui.',
            'data' => new ServiceTimeResource($serviceTime->fresh()),
        ]);
    }

    /**
     * Menghapus waktu layanan.
     */
    public function destroy(WaktuLayanan $serviceTime)
    {
        // Waktu layanan yang masih dipakai layanan tidak boleh dihapus.
        if ($serviceTime->layanan()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Waktu layanan tidak dapat dihapus karena masih digunakan oleh layanan.',
            ], 422);
        }

        $serviceTime->delete();

        return response()->json([
            'success' => true,
            'message' => 'Waktu layanan berhasil dihapus.',
        ]);
    }
}
