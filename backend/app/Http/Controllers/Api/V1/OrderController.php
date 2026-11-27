<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\StoreOrderRequest;
use App\Http\Requests\Api\V1\Order\UpdateOrderRequest;
use App\Http\Resources\Api\V1\DeliveryTaskResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\Pesanan;
use App\Services\OrderReceiptImage;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /** Status yang diizinkan, termasuk tahapan proses laundry dari scan QR. */
    private const ORDER_STATUSES = [
        'menunggu',
        'dicuci',
        'dikeringkan',
        'dilipat',
        'disetrika',
        'diproses',
        'siap_diambil',
        'selesai',
        'dibatalkan',
    ];

    /**
     * Hitung estimasi selesai dari durasi terlama yang dipilih.
     *
     * Durasi layanan tersimpan dalam jam (estimasi_max_jam, fallback ke
     * estimasi_min_jam). Estimasi dihitung dari tanggal masuk, sehingga
     * nilai yang disimpan di database selalu konsisten dengan preview
     * yang ditampilkan di form.
     */
    private function hitungEstimasiSelesai($layanans, $details, $tanggalMasuk): ?Carbon
    {
        $estimasiJam = collect($details)
            ->map(function ($detail) use ($layanans) {
                $idLayanan = is_array($detail)
                    ? ($detail['id_layanan'] ?? null)
                    : ($detail->id_layanan ?? null);

                $waktu = $layanans->get($idLayanan)?->waktuLayanan;

                if (!$waktu) {
                    return null;
                }

                $max = (int) ($waktu->estimasi_max_jam ?? 0);
                $min = (int) ($waktu->estimasi_min_jam ?? 0);

                return $max > 0 ? $max : ($min > 0 ? $min : null);
            })
            ->filter(fn ($jam) => $jam !== null && $jam > 0)
            ->max();

        if (!$estimasiJam) {
            return null;
        }

        return Carbon::parse($tanggalMasuk)->addHours((int) $estimasiJam);
    }

    private function orderQuery()
    {
        return Pesanan::with([
            'detailPesanan.layanan.waktuLayanan',
            'detailPesanan.prosesLaundry.riwayatProses',
            'pembayaran',
            'pembayaran.metodePembayaran',
            'pembayaran.user',
            'riwayatStatus.user',
            'antarJemput.kurir',
        ]);
    }

    public function index(Request $request)
    {
        $query = $this->orderQuery();

        $query->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;

            $query->where(function ($query) use ($search) {
                $query->where('kode_pesanan', 'like', "%{$search}%")
                    ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%");
            });
        });

        $query->when($request->filled('status_pesanan'), function ($query) use ($request) {
            $query->where('status_pesanan', $request->status_pesanan);
        });

        $query->when($request->filled('tanggal_mulai'), function ($query) use ($request) {
            $query->whereDate('tanggal_masuk', '>=', $request->tanggal_mulai);
        });

        $query->when($request->filled('tanggal_selesai'), function ($query) use ($request) {
            $query->whereDate('tanggal_masuk', '<=', $request->tanggal_selesai);
        });

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $orders = $query
            ->latest('tanggal_masuk')
            ->paginate($perPage)
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    public function show(Pesanan $order)
    {
        $order->load([
            'detailPesanan.layanan.waktuLayanan',
            'detailPesanan.prosesLaundry.riwayatProses',
            'pembayaran',
            'pembayaran.metodePembayaran',
            'pembayaran.user',
            'riwayatStatus.user',
            'antarJemput.kurir',
        ]);

        return new OrderResource($order);
    }

    /**
     * Cari pesanan berdasarkan kode yang tertanam pada QR.
     */
    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'kode_pesanan' => ['required', 'string', 'max:50'],
        ]);

        $kode = Str::upper(trim($validated['kode_pesanan']));

        $order = $this->orderQuery()
            ->where('kode_pesanan', $kode)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan dengan kode tersebut tidak ditemukan.',
            ], 404);
        }

        return new OrderResource($order);
    }

    /**
     * Cetak struk pesanan sebagai PNG, lengkap dengan QR untuk update status.
     */
    public function receipt(Pesanan $order, OrderReceiptImage $receipt)
    {
        return response($receipt->generate($order), 200)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'inline; filename="struk-' . $order->kode_pesanan . '.png"');
    }

    /**
     * Buat tugas pengantaran dari pesanan yang sudah siap diambil.
     *
     * Tugas dibuat tanpa kurir agar tampil sebagai "menunggu kurir" dan
     * diambil sendiri oleh kurir lewat endpoint accept.
     */
    public function createDelivery(Request $request, Pesanan $order)
    {
        $validated = $request->validate([
            'alamat_tujuan' => ['nullable', 'string'],
            'nama_penerima' => ['nullable', 'string', 'max:150'],
            'no_hp_penerima' => ['nullable', 'string', 'max:20'],
            'jadwal' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $order) {
            $order = Pesanan::whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status_pesanan !== 'siap_diambil') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan hanya dapat diantar saat statusnya siap diambil.',
                ], 422);
            }

            $tugasAktif = $order->antarJemput()
                ->where('jenis_antar_jemput', 'pengantaran')
                ->whereNotIn('status_antar_jemput', ['selesai', 'gagal'])
                ->exists();

            if ($tugasAktif) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan ini sudah memiliki tugas pengantaran yang masih berjalan.',
                ], 422);
            }

            $alamatTujuan = trim((string) ($validated['alamat_tujuan'] ?? $order->alamat));

            if ($alamatTujuan === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Alamat pelanggan belum tersedia. Lengkapi alamat pesanan terlebih dahulu.',
                ], 422);
            }

            $task = $order->antarJemput()->create([
                'id_kurir' => null,
                'jenis_antar_jemput' => 'pengantaran',
                'status_antar_jemput' => 'menunggu',
                'alamat_tujuan' => $alamatTujuan,
                'nama_penerima' => $validated['nama_penerima'] ?? $order->nama_pelanggan,
                'no_hp_penerima' => $validated['no_hp_penerima'] ?? $order->no_hp,
                'jadwal' => $validated['jadwal'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $task->riwayatAntarJemput()->create([
                'id_user' => Auth::id(),
                'status_sebelumnya' => null,
                'status_selanjutnya' => 'menunggu',
                'keterangan' => 'Tugas pengantaran dibuat dari pesanan siap diambil.',
                'tanggal_perubahan' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tugas pengantaran dibuat. Menunggu kurir menerima tugas.',
                'data' => new DeliveryTaskResource($task->load([
                    'pesanan',
                    'kurir',
                    'riwayatAntarJemput' => fn ($query) => $query
                        ->with('user')
                        ->latest('tanggal_perubahan'),
                ])),
            ], 201);
        });
    }

    public function store(StoreOrderRequest $request)
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('orders.create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk membuat pesanan.',
            ], 403);
        }

        $validated = $request->validated();

        $details = $validated['detail_pesanan'];

        $layananIds = collect($details)
            ->pluck('id_layanan')
            ->unique()
            ->values();

        $layanans = Layanan::with('waktuLayanan')
            ->whereIn('id_layanan', $layananIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id_layanan');

        if ($layanans->count() !== $layananIds->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Terdapat layanan yang tidak aktif atau tidak ditemukan.',
            ], 422);
        }

        $estimasiSelesai = $this->hitungEstimasiSelesai(
            $layanans,
            $details,
            $validated['tanggal_masuk']
        );

        $subtotal = 0;

        foreach ($details as $detail) {
            $layanan = $layanans->get($detail['id_layanan']);

            $subtotal += (float) $layanan->harga * (float) $detail['jumlah'];
        }

        $subtotal = round($subtotal, 2);
        $diskon = round((float) ($validated['diskon'] ?? 0), 2);

        if ($diskon > $subtotal) {
            return response()->json([
                'success' => false,
                'message' => 'Diskon tidak boleh melebihi subtotal pesanan.',
            ], 422);
        }

        $caraBayar = $validated['cara_bayar'] ?? 'later';

        $order = DB::transaction(function () use (
            $validated,
            $details,
            $layanans,
            $estimasiSelesai,
            $subtotal,
            $diskon,
            $caraBayar
        ) {
            $order = Pesanan::create([
                'id_user' => Auth::id(),
                'kode_pesanan' => 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8)),
                'nama_pelanggan' => $validated['nama_pelanggan'],
                'no_hp' => $validated['no_hp'],
                'alamat' => $validated['alamat'] ?? null,
                'tanggal_masuk' => $validated['tanggal_masuk'],
                'estimasi_selesai' => $estimasiSelesai,
                'status_pesanan' => 'menunggu',
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total_harga' => $subtotal - $diskon,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($details as $detail) {
                $layanan = $layanans->get($detail['id_layanan']);

                $detailPesanan = $order->detailPesanan()->create([
                    'id_layanan' => $layanan->id_layanan,
                    'nama_layanan' => $layanan->nama_layanan,
                    'harga_satuan' => $layanan->harga,
                    'jumlah' => $detail['jumlah'],
                    'satuan' => $layanan->jenis_layanan === 'kiloan' ? 'kg' : 'pcs',
                    'subtotal' => round(
                        (float) $layanan->harga * (float) $detail['jumlah'],
                        2
                    ),
                    'catatan' => $detail['catatan'] ?? null,
                ]);

                $detailPesanan->prosesLaundry()->create([
                    'status_proses' => 'menunggu',
                ]);
            }

            if ($caraBayar === 'now') {
                $metode = MetodePembayaran::find($validated['id_metode_pembayaran']);
                $statusPembayaran = ($validated['status_pembayaran'] ?? 'pending') === 'berhasil'
                    ? 'berhasil'
                    : 'menunggu';
                $jenisPembayaran = (float) $validated['jumlah_bayar'] >= (float) ($subtotal - $diskon)
                    ? 'pelunasan'
                    : 'dp';

                $order->pembayaran()->create([
                    'id_metode_pembayaran' => $validated['id_metode_pembayaran'],
                    'id_user' => Auth::id(),
                    'kode_pembayaran' => 'PAY-' . now()->format('Ymd') . '-' . Str::upper(Str::random(10)),
                    'jumlah_bayar' => $validated['jumlah_bayar'],
                    'jenis_pembayaran' => $jenisPembayaran,
                    'status_pembayaran' => $statusPembayaran,
                    'tanggal_pembayaran' => now(),
                    'catatan' => 'Pembayaran via ' . ($metode?->nama_metode ?? '-')
                        . ' atas pesanan ' . $order->kode_pesanan,
                ]);
            }

            $order->riwayatStatus()->create([
                'id_user' => Auth::id(),
                'status_sebelumnya' => null,
                'status_selanjutnya' => 'menunggu',
                'keterangan' => 'Pesanan dibuat.',
                'tanggal_perubahan' => now(),
            ]);

            return $order;
        });

        $order->load([
            'detailPesanan.layanan.waktuLayanan',
            'detailPesanan.prosesLaundry',
            'pembayaran',
            'pembayaran.metodePembayaran',
            'pembayaran.user',
            'riwayatStatus.user',
        ]);

        // Kirim notifikasi WhatsApp via Waha (sudah setelah detailPesanan di-load)
        app(WahaService::class)->sendOrderInvoice($order);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat.',
            'data' => new OrderResource($order),
        ], 201);
    }

    public function update(UpdateOrderRequest $request, Pesanan $order)
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $order) {
            $order = Pesanan::whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($order->status_pesanan, ['selesai', 'dibatalkan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan yang sudah selesai atau dibatalkan tidak dapat diubah.',
                ], 422);
            }

            $ubahDetail = array_key_exists('detail_pesanan', $validated);

            $detailData = $ubahDetail
                ? $validated['detail_pesanan']
                : $order->detailPesanan;

            $layanans = collect();

            if ($ubahDetail) {
                $layananIds = collect($detailData)
                    ->pluck('id_layanan')
                    ->unique()
                    ->values();

                $layanans = Layanan::with('waktuLayanan')
                    ->whereIn('id_layanan', $layananIds)
                    ->where('is_active', true)
                    ->get()
                    ->keyBy('id_layanan');

                if ($layanans->count() !== $layananIds->count()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Terdapat layanan yang tidak aktif atau tidak ditemukan.',
                    ], 422);
                }

                $order->load('detailPesanan.prosesLaundry.riwayatProses');

                foreach ($order->detailPesanan as $detail) {
                    $proses = $detail->prosesLaundry;

                    if (
                        !$proses
                        || $proses->status_proses !== 'menunggu'
                        || $proses->riwayatProses->isNotEmpty()
                    ) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Detail pesanan tidak dapat diubah karena proses laundry sudah berjalan.',
                        ], 422);
                    }
                }
            }

            $subtotal = 0;

            foreach ($detailData as $detail) {
                $harga = $ubahDetail
                    ? (float) $layanans->get($detail['id_layanan'])->harga
                    : (float) $detail->harga_satuan;

                $jumlah = $ubahDetail
                    ? (float) $detail['jumlah']
                    : (float) $detail->jumlah;

                $subtotal += $harga * $jumlah;
            }

            $subtotal = round($subtotal, 2);
            $diskon = round(
                (float) ($validated['diskon'] ?? $order->diskon),
                2
            );

            if ($diskon > $subtotal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Diskon tidak boleh melebihi subtotal pesanan.',
                ], 422);
            }

            $totalHarga = round($subtotal - $diskon, 2);

            $totalTerbayar = (float) $order->pembayaran()
                ->where('status_pembayaran', 'berhasil')
                ->sum('jumlah_bayar');

            if ($totalHarga < $totalTerbayar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total pesanan tidak boleh lebih kecil dari jumlah pembayaran yang sudah diterima.',
                ], 422);
            }

            // Hitung ulang estimasi bila layanan dan/atau tanggal masuk berubah.
            if (!$ubahDetail) {
                $layanans = Layanan::with('waktuLayanan')
                    ->whereIn('id_layanan', $order->detailPesanan->pluck('id_layanan'))
                    ->get()
                    ->keyBy('id_layanan');
            }

            $estimasiSelesai = $this->hitungEstimasiSelesai(
                $layanans,
                $detailData,
                $validated['tanggal_masuk'] ?? $order->tanggal_masuk
            );

            $order->update([
                'nama_pelanggan' => $validated['nama_pelanggan'] ?? $order->nama_pelanggan,
                'no_hp' => $validated['no_hp'] ?? $order->no_hp,
                'alamat' => array_key_exists('alamat', $validated)
                    ? $validated['alamat']
                    : $order->alamat,
                'tanggal_masuk' => $validated['tanggal_masuk'] ?? $order->tanggal_masuk,
                'estimasi_selesai' => $estimasiSelesai,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total_harga' => $totalHarga,
                'catatan' => array_key_exists('catatan', $validated)
                    ? $validated['catatan']
                    : $order->catatan,
            ]);

            if ($ubahDetail) {
                $order->detailPesanan()->delete();

                foreach ($detailData as $detail) {
                    $layanan = $layanans->get($detail['id_layanan']);

                    $detailPesanan = $order->detailPesanan()->create([
                        'id_layanan' => $layanan->id_layanan,
                        'nama_layanan' => $layanan->nama_layanan,
                        'harga_satuan' => $layanan->harga,
                        'jumlah' => $detail['jumlah'],
                        'satuan' => $layanan->jenis_layanan === 'kiloan' ? 'kg' : 'pcs',
                        'subtotal' => round(
                            (float) $layanan->harga * (float) $detail['jumlah'],
                            2
                        ),
                        'catatan' => $detail['catatan'] ?? null,
                    ]);

                    $detailPesanan->prosesLaundry()->create([
                        'status_proses' => 'menunggu',
                    ]);
                }
            }

            $order->load([
                'detailPesanan.layanan.waktuLayanan',
                'detailPesanan.prosesLaundry',
                'pembayaran',
                'pembayaran.metodePembayaran',
                'pembayaran.user',
                'riwayatStatus.user',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil diperbarui.',
                'data' => new OrderResource($order),
            ]);
        });
    }

    public function destroy(Pesanan $order)
    {
        $order->load('detailPesanan.prosesLaundry.riwayatProses');

        if ($order->pembayaran()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak dapat dihapus karena sudah memiliki data pembayaran.',
            ], 422);
        }

        foreach ($order->detailPesanan as $detail) {
            $proses = $detail->prosesLaundry;

            if (
                $proses
                && (
                    $proses->status_proses !== 'menunggu'
                    || $proses->riwayatProses->isNotEmpty()
                )
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan tidak dapat dihapus karena proses laundry sudah berjalan.',
                ], 422);
            }
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dihapus.',
        ]);
    }

    public function updateStatus(Request $request, Pesanan $order)
    {
        $validated = $request->validate([
            'status_pesanan' => [
                'required',
                'string',
                Rule::in(self::ORDER_STATUSES),
            ],
            'keterangan' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $order) {
            $order = Pesanan::whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $statusLama = $order->status_pesanan;
            $statusBaru = $validated['status_pesanan'];

            if ($statusLama === $statusBaru) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status pesanan sudah menggunakan status tersebut.',
                ], 422);
            }

            if (in_array($statusLama, ['selesai', 'dibatalkan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status pesanan yang sudah selesai atau dibatalkan tidak dapat diubah.',
                ], 422);
            }

            $order->update([
                'status_pesanan' => $statusBaru,
                'tanggal_selesai' => $statusBaru === 'selesai'
                    ? now()
                    : $order->tanggal_selesai,
            ]);

            $order->riwayatStatus()->create([
                'id_user' => Auth::id(),
                'status_sebelumnya' => $statusLama,
                'status_selanjutnya' => $statusBaru,
                'keterangan' => $validated['keterangan'] ?? null,
                'tanggal_perubahan' => now(),
            ]);

            $order->load([
                'detailPesanan.layanan.waktuLayanan',
                'detailPesanan.prosesLaundry',
                'pembayaran',
                'pembayaran.metodePembayaran',
                'pembayaran.user',
                'riwayatStatus.user',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status pesanan berhasil diperbarui.',
                'data' => new OrderResource($order),
            ]);
        });
    }
}
