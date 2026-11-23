<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\Api\V1\PaymentMethod\UpdatePaymentMethodRequest;
use App\Http\Resources\Api\V1\PaymentMethodResource;
use App\Models\MetodePembayaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $query = MetodePembayaran::query();

        if ($request->boolean('with_summary')) {
            $query->with([
                'pembayaran' => fn ($q) => $q
                    ->where('status_pembayaran', 'berhasil')
                    ->where('tanggal_pembayaran', '>=', now()->subDays(30)),
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('nama_metode', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $paymentMethods = $query
            ->latest('id_metode_pembayaran')
            ->paginate($request->integer('per_page', 10));

        return PaymentMethodResource::collection($paymentMethods);
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        $paymentMethod = MetodePembayaran::create($request->validated());

        return response()->json([
            'message' => 'Metode pembayaran berhasil ditambahkan.',
            'data' => new PaymentMethodResource($paymentMethod),
        ], 201);
    }

    public function show(MetodePembayaran $paymentMethod): PaymentMethodResource
    {
        return new PaymentMethodResource($paymentMethod);
    }

    public function update(
        UpdatePaymentMethodRequest $request,
        MetodePembayaran $paymentMethod
    ): JsonResponse {
        $paymentMethod->update($request->validated());

        return response()->json([
            'message' => 'Metode pembayaran berhasil diperbarui.',
            'data' => new PaymentMethodResource($paymentMethod->refresh()),
        ]);
    }

    public function updateStatus(Request $request, MetodePembayaran $paymentMethod): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $paymentMethod->update($validated);

        return response()->json([
            'message' => 'Status metode pembayaran berhasil diperbarui.',
            'data' => new PaymentMethodResource($paymentMethod->refresh()),
        ]);
    }

    public function destroy(MetodePembayaran $paymentMethod): JsonResponse
    {
        if ($paymentMethod->pembayaran()->exists()) {
            return response()->json([
                'message' => 'Metode pembayaran tidak dapat dihapus karena sudah digunakan dalam transaksi.',
            ], 422);
        }

        $paymentMethod->delete();

        return response()->json([
            'message' => 'Metode pembayaran berhasil dihapus.',
        ]);
    }
}