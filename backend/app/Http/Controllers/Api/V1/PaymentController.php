<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\StorePaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    private function paymentQuery()
    {
        return Pembayaran::with([
            'pesanan',
            'metodePembayaran',
            'user',
        ]);
    }

    /**
     * Riwayat seluruh pembayaran (dipakai admin & owner secara read-only).
     */
    public function index(Request $request)
    {
        $query = $this->paymentQuery();

        $query->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;

            $query->where(function ($query) use ($search) {
                $query->where('kode_pembayaran', 'like', "%{$search}%")
                    ->orWhere('catatan', 'like', "%{$search}%")
                    ->orWhereHas('pesanan', function ($query) use ($search) {
                        $query->where('kode_pesanan', 'like', "%{$search}%")
                            ->orWhere('nama_pelanggan', 'like', "%{$search}%");
                    });
            });
        });

        $query->when($request->filled('status_pembayaran'), function ($query) use ($request) {
            $query->where('status_pembayaran', $request->status_pembayaran);
        });

        $query->when($request->filled('jenis_pembayaran'), function ($query) use ($request) {
            $query->where('jenis_pembayaran', $request->jenis_pembayaran);
        });

        $query->when($request->filled('id_metode_pembayaran'), function ($query) use ($request) {
            $query->where('id_metode_pembayaran', $request->id_metode_pembayaran);
        });

        $query->when($request->filled('tanggal_mulai'), function ($query) use ($request) {
            $query->whereDate('tanggal_pembayaran', '>=', $request->tanggal_mulai);
        });

        $query->when($request->filled('tanggal_selesai'), function ($query) use ($request) {
            $query->whereDate('tanggal_pembayaran', '<=', $request->tanggal_selesai);
        });

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return PaymentResource::collection(
            $query
                ->latest('tanggal_pembayaran')
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function show(Pembayaran $payment)
    {
        $payment->load([
            'pesanan',
            'metodePembayaran',
            'user',
        ]);

        return new PaymentResource($payment);
    }

    /**
     * Catat pembayaran lanjutan untuk pesanan yang dibayar nanti (DP/angsuran/pelunasan).
     */
    public function store(StorePaymentRequest $request)
    {
        $validated = $request->validated();

        $payment = DB::transaction(function () use ($validated) {
            $order = Pesanan::whereKey($validated['id_pesanan'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status_pesanan === 'dibatalkan') {
                throw ValidationException::withMessages([
                    'id_pesanan' => 'Pesanan yang dibatalkan tidak dapat menerima pembayaran.',
                ]);
            }

            $terbayar = (float) $order->pembayaran()
                ->where('status_pembayaran', 'berhasil')
                ->sum('jumlah_bayar');

            $sisa = round((float) $order->total_harga - $terbayar, 2);
            $jumlah = round((float) $validated['jumlah_bayar'], 2);

            if ($sisa <= 0) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Tagihan pesanan ini sudah lunas.',
                ]);
            }

            if ($jumlah > $sisa) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => "Jumlah bayar tidak boleh melebihi sisa tagihan (" . number_format($sisa, 2, ',', '.') . ").",
                ]);
            }

            if ($validated['jenis_pembayaran'] === 'pelunasan' && abs($jumlah - $sisa) > 0.01) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Pelunasan harus sama dengan sisa tagihan (' . number_format($sisa, 2, ',', '.') . ").",
                ]);
            }

            return $order->pembayaran()->create([
                'id_metode_pembayaran' => $validated['id_metode_pembayaran'],
                'id_user' => Auth::id(),
                'kode_pembayaran' => 'PAY-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'jumlah_bayar' => $validated['jumlah_bayar'],
                'jenis_pembayaran' => $validated['jenis_pembayaran'],
                'status_pembayaran' => $validated['status_pembayaran'] ?? 'berhasil',
                'tanggal_pembayaran' => $validated['tanggal_pembayaran'] ?? now(),
                'catatan' => $validated['catatan'] ?? null,
            ]);
        });

        $payment->load([
            'pesanan',
            'metodePembayaran',
            'user',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dicatat.',
            'data' => new PaymentResource($payment),
        ], 201);
    }
}
