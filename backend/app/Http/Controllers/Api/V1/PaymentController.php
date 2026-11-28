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
use Illuminate\Database\QueryException;

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
            // Cek juga apakah key digunakan untuk pesanan lain (global unique constraint).
            $existingSameOrder = Pembayaran::where('idempotency_key', $idempotencyKey)
                ->where('id_pesanan', $validated['id_pesanan'])
                ->first();

            $existingDifferentOrder = Pembayaran::where('idempotency_key', $idempotencyKey)
                ->where('id_pesanan', '!=', $validated['id_pesanan'])
                ->first();

            if ($existingDifferentOrder) {
                // Key sudah digunakan untuk pesanan lain → conflict
                throw new ConflictHttpException(
                    'Idempotency key sudah digunakan untuk transaksi lain.'
                );
            }

            if ($existingSameOrder) {
                if (hash_equals((string) $existingSameOrder->payload_hash, $payloadHash)) {
                    $existingSameOrder->load([
                        'pesanan',
                        'metodePembayaran',
                        'user',
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Pembayaran sudah dicatat.',
                        'data' => new PaymentResource($existingSameOrder),
                    ], 200);
                }

                throw new ConflictHttpException(
                    'Idempotency key sudah digunakan dengan payload berbeda.'
                );
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

            // Validasi pelunasan: nominal harus sama dengan sisa tagihan.
            if ($validated['jenis_pembayaran'] === 'pelunasan' && abs($amountReceived - $outstanding) > 0.01) {
                throw ValidationException::withMessages([
                    'amount_received' => 'Pelunasan harus sama dengan sisa tagihan (' . number_format($outstanding, 2, '.', '') . ').',
                ]);
            }

            // Pastikan kedua variabel selalu terdefinisi.
            $amountApplied = 0.0;
            $changeAmount = 0.0;

            if ($isCash) {
                // Tunai: kelebihan pembayaran menjadi kembalian.
                $amountApplied = min($amountReceived, $outstanding);
                $changeAmount = max(0, $amountReceived - $amountApplied);
            } else {
                // Transfer/QRIS: tidak menerima kelebihan pembayaran, status default menunggu.
                if ($amountReceived > $outstanding) {
                    throw ValidationException::withMessages([
                        'amount_received' => 'Jumlah uang diterima melebihi sisa tagihan.',
                    ]);
                }
                $amountApplied = $amountReceived;
                // Untuk transfer/QRIS, status dibiarkan 'menunggu' hingga diverifikasi Owner/Admin.
                // Jika frontend mengirim 'berhasil', tetap catat sebagai 'menunggu' di sisi backend
                // agar hanya Owner/Admin yang dapat mengesakapnya nanti.
            }

            if ($amountApplied <= 0) {
                throw ValidationException::withMessages([
                    'amount_received' => 'Nominal bayar tidak valid.',
                ]);
            }

            // Determin status pembayaran:
            // - Tunai: gunakan status dari request (boleh 'berhasil' atau 'menunggu')
            // - Transfer/QRIS: default 'menunggu' (butuh verifikasi Owner/Admin)
            if ($isCash) {
                $statusPembayaran = $validated['status_pembayaran'] ?? 'berhasil';
            } else {
                $statusPembayaran = 'menunggu';
            }

            // Simpan pembayaran (jika terjadi duplicate key di database, akan dilempar QueryException)
            try {
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
                    'status_pembayaran' => $statusPembayaran,
                    'tanggal_pembayaran' => now(),
                    'catatan' => $validated['catatan'] ?? null,
                    'payload_hash' => $payloadHash,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Tangani constraint violation dari database (duplicate key)
                $errorMessage = $e->getMessage();

                // Periksa apakah ini error duplicate entry untuk idempotency_key
                if (str_contains($errorMessage, 'idempotency_key') || str_contains($errorMessage, 'pembayaran_idempotency_key_unique')) {
                    // Cari transaksi lama berdasarkan key
                    $existing = Pembayaran::where('idempotency_key', $idempotencyKey)
                        ->where('id_pesanan', $validated['id_pesanan'])
                        ->first();

                    if ($existing) {
                        // Periksa payload hash
                        $payloadHashNew = hash('sha256', json_encode($validated, JSON_THROW_ON_ERROR));
                        if (hash_equals((string) $existing->payload_hash, $payloadHashNew)) {
                            // Payload sama → kembalikan transaksi lama (idempotent)
                            $existing->load(['pesanan', 'metodePembayaran', 'user']);
                            return response()->json([
                                'success' => true,
                                'message' => 'Pembayaran sudah dicatat.',
                                'data' => new PaymentResource($existing),
                            ], 200);
                        }
                        // Payload berbeda → conflict
                        throw new ConflictHttpException(
                            'Idempotency key sudah digunakan dengan payload berbeda.'
                        );
                    }
                }

                // Error database lain — jangan menyamarkan sebagai konflik idempotency
                throw $e;
            }

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

    /**
     * Verifikasi pembayaran manual (Transfer Bank, QRIS).
     * Hanya Owner dan Admin yang dapat memverifikasi.
     * Mengubah status dari 'menunggu' menjadi 'berhasil'.
     */
    public function verify(Pembayaran $payment)
    {
        $payment->load([
            'pesanan',
            'metodePembayaran',
        ]);

        // Validasi: status harus 'menunggu'
        if ($payment->status_pembayaran !== 'menunggu') {
            throw ValidationException::withMessages([
                'status_pembayaran' => "Hanya pembayaran dengan status 'menunggu' yang dapat diverifikasi.",
            ]);
        }

        // Validasi: metode pembayaran non-tunai
        if ($payment->metodePembayaran->kode !== 'transfer') {
            throw ValidationException::withMessages([
                'metode_pembayaran' => 'Hanya pembayaran transfer yang dapat diverifikasi.',
            ]);
        }

        return DB::transaction(function () use ($payment) {
            // Pastikan pesanan tidak dibatalkan
            if ($payment->pesanan->status_pesanan === 'dibatalkan') {
                throw ValidationException::withMessages([
                    'id_pesanan' => 'Pesanan yang dibatalkan tidak dapat menerima pembayaran.',
                ]);
            }

            // Hitung sisa tagihan hanya dari pembayaran berhasil
            $terbayar = (float) $payment->pesanan->pembayaran()
                ->where('status_pembayaran', 'berhasil')
                ->sum('amount_applied');

            $outstanding = round((float) $payment->pesanan->total_harga - $terbayar, 2);

            // Validasi: sisa tagihan harus >= nominal pembayaran
            if ($outstanding < $payment->amount_applied) {
                throw ValidationException::withMessages([
                    'id_pesanan' => 'Nominal pembayaran melebihi sisa tagihan yang tersedia.',
                ]);
            }

            // Update status menjadi 'berhasil'
            $payment->update([
                'status_pembayaran' => 'berhasil',
                'catatan' => trim($payment->catatan . ' [DIVERIFIKASI oleh diverifikator]'),
            ]);

            $payment->load([
                'pesanan',
                'metodePembayaran',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diverifikasi.',
                'data' => new PaymentResource($payment),
            ], 200);
        });
    }
}
