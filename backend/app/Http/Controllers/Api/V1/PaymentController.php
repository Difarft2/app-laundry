<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\StorePaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\MetodePembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
     * Catat pembayaran lanjutan untuk pesanan (DP/angsuran/pelunasan).
     */
    public function store(StorePaymentRequest $request)
    {
        $validated = $request->validated();

        $idempotencyKey = $validated['idempotency_key'] ?? null;

        if (!$idempotencyKey) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Idempotency key wajib diisi.',
            ]);
        }

        $payloadHash = hash('sha256', json_encode($validated, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($validated, $idempotencyKey, $payloadHash) {
            // Kunci pesanan untuk mencegah pembayaran bersamaan.
            $order = Pesanan::whereKey($validated['id_pesanan'])
                ->lockForUpdate()
                ->firstOrFail();

            // Pemeriksaan idempotency dilakukan setelah memperoleh lock.
            $existing = Pembayaran::where('idempotency_key', $idempotencyKey)
                ->where('id_pesanan', $validated['id_pesanan'])
                ->first();

            if ($existing) {
                if (hash_equals((string) $existing->payload_hash, $payloadHash)) {
                    $existing->load([
                        'pesanan',
                        'metodePembayaran',
                        'user',
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Pembayaran sudah dicatat.',
                        'data' => new PaymentResource($existing),
                    ], 200);
                }

                throw new ConflictHttpException(
                    'Idempotency key sudah digunakan dengan payload berbeda.'
                );
            }

            if ($order->status_pesanan === 'dibatalkan') {
                throw ValidationException::withMessages([
                    'id_pesanan' => 'Pesanan yang dibatalkan tidak dapat menerima pembayaran.',
                ]);
            }

            // Hitung sisa tagihan hanya dari pembayaran berhasil.
            $terbayar = (float) $order->pembayaran()
                ->where('status_pembayaran', 'berhasil')
                ->sum('amount_applied');

            $outstanding = round((float) $order->total_harga - $terbayar, 2);

            if ($outstanding <= 0) {
                throw ValidationException::withMessages([
                    'id_pesanan' => 'Tagihan pesanan ini sudah lunas.',
                ]);
            }

            $amountReceived = (float) $validated['amount_received'];

            $metode = MetodePembayaran::findOrFail(
                $validated['id_metode_pembayaran']
            );

            $isCash = $metode->kode === 'tunai';

            // Pastikan kedua variabel selalu terdefinisi.
            $amountApplied = 0.0;
            $changeAmount = 0.0;

            if ($isCash) {
                // Tunai: kelebihan pembayaran menjadi kembalian.
                $amountApplied = min($amountReceived, $outstanding);
                $changeAmount = max(0, $amountReceived - $amountApplied);
            } else {
                // Transfer/QRIS tidak menerima kelebihan pembayaran.
                if ($amountReceived > $outstanding) {
                    throw ValidationException::withMessages([
                        'amount_received' => 'Jumlah uang diterima melebihi sisa tagihan.',
                    ]);
                }

                $amountApplied = $amountReceived;
            }

            if ($amountApplied <= 0) {
                throw ValidationException::withMessages([
                    'amount_received' => 'Nominal bayar tidak valid.',
                ]);
            }

            $payment = $order->pembayaran()->create([
                'id_metode_pembayaran' => $validated['id_metode_pembayaran'],
                'id_user' => Auth::id(),
                'kode_pembayaran' => 'PAY-' . now()->format('Ymd') . '-' . Str::upper(Str::random(10)),
                'jumlah_bayar' => $amountApplied,
                'idempotency_key' => $idempotencyKey,
                'amount_received' => $amountReceived,
                'amount_applied' => $amountApplied,
                'change_amount' => $changeAmount,
                'jenis_pembayaran' => $validated['jenis_pembayaran'],
                'status_pembayaran' => 'berhasil',
                'tanggal_pembayaran' => now(),
                'catatan' => $validated['catatan'] ?? null,
                'payload_hash' => $payloadHash,
            ]);

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
        }, 3);
    }
}
