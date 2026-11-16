<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Service\StoreServiceRequest;
use App\Http\Requests\Api\V1\Service\UpdateServiceRequest;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\Layanan;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Menampilkan daftar layanan.
     */
    public function index(Request $request)
    {
        $query = Layanan::with('waktuLayanan');

        // Pencarian nama layanan.
        $query->when($request->filled('search'), function ($query) use ($request) {
            $query->where(
                'nama_layanan',
                'like',
                '%' . $request->search . '%'
            );
        });

        // Filter jenis layanan.
        $query->when($request->filled('jenis_layanan'), function ($query) use ($request) {
            $query->where('jenis_layanan', $request->jenis_layanan);
        });

        // Filter status aktif.
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min(
            max((int) $request->input('per_page', 15), 1),
            100
        );

        $services = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return ServiceResource::collection($services);
    }

    /**
     * Menampilkan detail layanan.
     */
    public function show(Layanan $service)
    {
        $service->load('waktuLayanan');

        return new ServiceResource($service);
    }

    /**
     * Menambahkan layanan baru.
     */
    public function store(StoreServiceRequest $request)
    {
        $service = Layanan::create($request->validated());

        $service->load('waktuLayanan');

        return response()->json([
            'success' => true,
            'message' => 'Layanan berhasil ditambahkan.',
            'data' => new ServiceResource($service),
        ], 201);
    }

    /**
     * Memperbarui layanan.
     */
    public function update(
        UpdateServiceRequest $request,
        Layanan $service
    ) {
        $service->update($request->validated());

        $service->load('waktuLayanan');

        return response()->json([
            'success' => true,
            'message' => 'Layanan berhasil diperbarui.',
            'data' => new ServiceResource($service),
        ]);
    }

    /**
     * Menghapus layanan.
     */
    public function destroy(Layanan $service)
    {
        // Layanan yang sudah digunakan dalam pesanan tidak boleh dihapus.
        if ($service->detailPesanan()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan tidak dapat dihapus karena sudah digunakan dalam pesanan.',
            ], 422);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Layanan berhasil dihapus.',
        ]);
    }
}
