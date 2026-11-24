<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DeliveryTaskResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\AntarJemput;
use App\Models\MetodePembayaran;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Models\Pesanan;
use App\Models\ProsesLaundry;
use App\Models\RiwayatAntarJemput;
use App\Models\RiwayatProsesLaundry;
use App\Models\RiwayatStatusPesanan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const ROLES = ['owner', 'admin', 'karyawan', 'kurir'];

    private const PENDING_DELIVERY_STATUSES = ['menunggu', 'ditugaskan', 'menuju_lokasi', 'barang_diambil', 'barang_diantar'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $this->roleOf($user);
        $isCourier = $role === 'kurir';

        $ordersQuery = $isCourier
            ? Pesanan::query()->whereRaw('1 = 0')
            : Pesanan::query();

        $tasksQuery = AntarJemput::query();
        $this->scopeTasks($request, $tasksQuery);

        $metrics = $isCourier
            ? $this->courierMetrics($request)
            : $this->operationalMetrics($ordersQuery, $tasksQuery);

        $recentTasks = (clone $tasksQuery)
            ->with(['pesanan', 'kurir', 'riwayatAntarJemput' => fn ($query) => $query
                ->with('user')
                ->latest('tanggal_perubahan')])
            ->latest('id_antar_jemput')
            ->limit(5)
            ->get();

        $activeTasks = (clone $tasksQuery)
            ->with(['pesanan', 'kurir'])
            ->whereIn('status_antar_jemput', self::PENDING_DELIVERY_STATUSES)
            ->latest('id_antar_jemput')
            ->limit(10)
            ->get();

        $completedTasks = (clone $tasksQuery)
            ->with(['pesanan', 'kurir'])
            ->where('status_antar_jemput', 'selesai')
            ->latest('tanggal_selesai')
            ->limit(10)
            ->get();

        $pickupRequests = (clone $tasksQuery)
            ->with(['pesanan', 'kurir'])
            ->where('jenis_antar_jemput', 'penjemputan')
            ->where('status_antar_jemput', 'menunggu')
            ->latest('id_antar_jemput')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'role' => $role,
                'metrics' => $metrics,
                'charts' => [
                    'revenue' => $isCourier
                        ? ['categories' => [], 'orders' => [], 'revenue' => []]
                        : $this->revenueChart($request),
                    'payment_methods' => $isCourier
                        ? []
                        : $this->paymentMethods(),
                    'expenses' => $isCourier
                        ? []
                        : $this->expenseBreakdown(),
                ],
                'recent' => [
                    'orders' => $isCourier
                        ? []
                        : OrderResource::collection(
                            (clone $ordersQuery)
                                ->with([
                                    'detailPesanan.layanan.waktuLayanan',
                                    'pembayaran',
                                    'pembayaran.metodePembayaran',
                                    'pembayaran.user',
                                    'riwayatStatus.user',
                                    'antarJemput.kurir',
                                ])
                                ->latest('tanggal_masuk')
                                ->limit(10)
                                ->get()
                        )->resolve($request),
                    'delivery_tasks' => DeliveryTaskResource::collection($recentTasks)->resolve($request),
                    'activities' => $this->recentActivities($request, $isCourier),
                ],
                'laundry' => [
                    'queue' => $isCourier ? [] : $this->laundryQueue($ordersQuery),
                ],
                'delivery' => [
                    'active_tasks' => DeliveryTaskResource::collection($activeTasks)->resolve($request),
                    'history' => DeliveryTaskResource::collection($completedTasks)->resolve($request),
                    'pickup_requests' => DeliveryTaskResource::collection($pickupRequests)->resolve($request),
                ],
            ],
        ]);
    }

    private function roleOf($user): string
    {
        foreach (self::ROLES as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return 'user';
    }

    private function scopeTasks(Request $request, $query): void
    {
        if (!$request->user()->hasRole('kurir')) {
            return;
        }

        $query->where(function ($query) use ($request) {
            $query->where('id_kurir', $request->user()->id)
                ->orWhere(function ($query) {
                    $query->whereNull('id_kurir')
                        ->where('status_antar_jemput', 'menunggu');
                });
        });
    }

    private function operationalMetrics($ordersQuery, $tasksQuery): array
    {
        $today = today();

        $totalOrders = (clone $ordersQuery)->count();
        $completedOrders = (clone $ordersQuery)
            ->where('status_pesanan', 'selesai')
            ->count();
        $newOrdersToday = (clone $ordersQuery)
            ->whereDate('tanggal_masuk', $today)
            ->count();
        $doneToday = (clone $ordersQuery)
            ->whereDate('tanggal_selesai', $today)
            ->count();

        $tagihan = (float) (clone $ordersQuery)
            ->where('status_pesanan', '!=', 'dibatalkan')
            ->sum('total_harga');
        $pembayaranBerhasil = (float) Pembayaran::where('status_pembayaran', 'berhasil')->sum('jumlah_bayar');
        $unpaid = max(0, $tagihan - $pembayaranBerhasil);

        $queueWashing = (int) ProsesLaundry::where('status_proses', 'menunggu')->count();
        $washingInProgress = (int) ProsesLaundry::where('status_proses', 'pencucian')->count();
        $finishing = (int) ProsesLaundry::whereIn('status_proses', ['pengeringan', 'finishing'])->count();

        $activeEmployees = (int) User::role('karyawan')->where('status', 'active')->count();
        $activeCouriers = (int) User::role('kurir')->where('status', 'active')->count();

        $waitingCourier = (int) (clone $tasksQuery)
            ->where('status_antar_jemput', 'menunggu')
            ->count();
        $pendingPickups = (int) (clone $tasksQuery)
            ->where('jenis_antar_jemput', 'penjemputan')
            ->whereNotIn('status_antar_jemput', ['selesai', 'gagal'])
            ->count();
        $onDelivery = (int) (clone $tasksQuery)
            ->where('jenis_antar_jemput', 'pengantaran')
            ->whereNotIn('status_antar_jemput', ['selesai', 'gagal'])
            ->count();
        $completedDeliveriesToday = (int) (clone $tasksQuery)
            ->where('jenis_antar_jemput', 'pengantaran')
            ->where('status_antar_jemput', 'selesai')
            ->whereDate('tanggal_selesai', $today)
            ->count();

        return [
            'totalOrders' => (int) $totalOrders,
            'completedOrders' => (int) $completedOrders,
            'revenue' => round($pembayaranBerhasil, 2),
            'expenses' => round((float) Pengeluaran::whereMonth('tanggal', $today->month)
                ->whereYear('tanggal', $today->year)
                ->sum('jumlah'), 2),
            'activeEmployees' => $activeEmployees,
            'activeCouriers' => $activeCouriers,
            'pendingPickups' => $pendingPickups,
            'newOrdersToday' => (int) $newOrdersToday,
            'queueWashing' => $queueWashing,
            'washingInProgress' => $washingInProgress,
            'finishing' => $finishing,
            'doneToday' => (int) $doneToday,
            'unpaid' => round($unpaid, 2),
            'waitingCourier' => $waitingCourier,
            'onDelivery' => $onDelivery,
            'completedDeliveriesToday' => $completedDeliveriesToday,
        ];
    }

    private function courierMetrics(Request $request): array
    {
        $userId = $request->user()->id;
        $today = today();
        $base = AntarJemput::where('id_kurir', $userId);

        return [
            'totalOrders' => 0,
            'completedOrders' => 0,
            'revenue' => 0,
            'expenses' => 0,
            'activeEmployees' => 0,
            'activeCouriers' => 0,
            'pendingPickups' => 0,
            'newOrdersToday' => 0,
            'queueWashing' => 0,
            'washingInProgress' => 0,
            'finishing' => 0,
            'doneToday' => 0,
            'unpaid' => 0,
            'waitingCourier' => (int) AntarJemput::whereNull('id_kurir')
                ->where('status_antar_jemput', 'menunggu')
                ->count(),
            'onDelivery' => (int) (clone $base)
                ->where('jenis_antar_jemput', 'pengantaran')
                ->whereNotIn('status_antar_jemput', ['selesai', 'gagal'])
                ->count(),
            'myTasks' => (int) (clone $base)
                ->whereNotIn('status_antar_jemput', ['selesai', 'gagal'])
                ->count(),
            'myCompletedToday' => (int) (clone $base)
                ->where('status_antar_jemput', 'selesai')
                ->whereDate('tanggal_selesai', $today)
                ->count(),
        ];
    }

    private function revenueChart(Request $request): array
    {
        $period = $request->query('period', '7d');

        if ($period === 'month') {
            $start = now()->startOfMonth();
            $days = now()->day;
            $label = 'Bulan Ini';
        } elseif ($period === '30d') {
            $start = now()->startOfDay()->subDays(29);
            $days = 30;
            $label = '30 Hari';
        } else {
            $period = '7d';
            $start = now()->startOfDay()->subDays(6);
            $days = 7;
            $label = '7 Hari';
        }

        $categories = [];
        $orders = [];
        $revenue = [];

        for ($offset = 0; $offset < $days; $offset += 1) {
            $date = $start->copy()->addDays($offset);
            $key = $date->toDateString();

            $categories[] = $period === 'month'
                ? (string) $date->day
                : $date->isoFormat('D MMM');
            $orders[] = (int) Pesanan::whereDate('tanggal_masuk', $key)->count();
            $revenue[] = round((float) Pembayaran::where('status_pembayaran', 'berhasil')
                ->whereDate('tanggal_pembayaran', $key)
                ->sum('jumlah_bayar'), 2);
        }

        return [
            'id' => $period,
            'label' => $label,
            'categories' => $categories,
            'orders' => $orders,
            'revenue' => $revenue,
        ];
    }

    private function paymentMethods(): array
    {
        return MetodePembayaran::with(['pembayaran' => fn ($query) => $query
            ->where('status_pembayaran', 'berhasil')])
            ->orderBy('nama_metode')
            ->get()
            ->map(fn (MetodePembayaran $method) => [
                'id' => $method->id_metode_pembayaran,
                'label' => $method->nama_metode,
                'kode' => $method->kode,
                'amount' => round((float) $method->pembayaran->sum('jumlah_bayar'), 2),
                'transactions' => (int) $method->pembayaran->count(),
            ])
            ->values()
            ->all();
    }

    private function laundryQueue($ordersQuery): array
    {
        return (clone $ordersQuery)
            ->with('detailPesanan')
            ->whereIn('status_pesanan', [
                'menunggu', 'dicuci', 'dikeringkan', 'dilipat', 'disetrika', 'diproses', 'siap_diambil',
            ])
            ->latest('tanggal_masuk')
            ->limit(10)
            ->get()
            ->map(fn (Pesanan $order) => [
                'id_pesanan' => $order->id_pesanan,
                'kode_pesanan' => $order->kode_pesanan,
                'pelanggan' => [
                    'nama' => $order->nama_pelanggan,
                    'no_hp' => $order->no_hp,
                ],
                'tanggal_masuk' => $order->tanggal_masuk?->toIso8601String(),
                'estimasi_selesai' => $order->estimasi_selesai?->toIso8601String(),
                'status_pesanan' => $order->status_pesanan,
                'layanan' => $order->detailPesanan->first()?->nama_layanan ?? '-',
                'jumlah_layanan' => $order->detailPesanan->count(),
                'total_jumlah' => (float) $order->detailPesanan->sum('jumlah'),
                'satuan' => $order->detailPesanan->first()?->satuan ?? '',
            ])
            ->values()
            ->all();
    }

    private function expenseBreakdown(): array
    {
        $items = Pengeluaran::query()
            ->whereMonth('tanggal', today()->month)
            ->whereYear('tanggal', today()->year)
            ->selectRaw('kategori, SUM(jumlah) as total')
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->get();

        $grandTotal = (float) $items->sum('total');

        return $items
            ->map(fn ($item, int $index) => [
                'id' => 'expense-' . ($index + 1),
                'label' => $item->kategori,
                'amount' => round((float) $item->total, 2),
                'percentage' => $grandTotal > 0
                    ? (int) round(((float) $item->total / $grandTotal) * 100)
                    : 0,
            ])
            ->values()
            ->all();
    }

    private function recentActivities(Request $request, bool $isCourier): array
    {
        $activities = collect();

        if (!$isCourier) {
            $activities = $activities->concat(
                RiwayatStatusPesanan::with(['user.roles', 'pesanan'])
                    ->latest('tanggal_perubahan')
                    ->limit(5)
                    ->get()
                    ->map(fn ($item) => [
                        'id' => 'status-' . $item->id_riwayat_status_pesanan,
                        'type' => 'order',
                        'title' => 'Status pesanan ' . ($item->pesanan?->kode_pesanan ?? '-') . ' diperbarui',
                        'description' => $item->status_sebelumnya . ' → ' . $item->status_selanjutnya,
                        'user' => $item->user?->name,
                        'role' => $item->user?->roles->first()?->name,
                        'time' => $item->tanggal_perubahan?->toIso8601String(),
                    ])
            );

            $activities = $activities->concat(
                RiwayatProsesLaundry::with(['user.roles', 'prosesLaundry.detailPesanan.pesanan'])
                    ->latest('tanggal_perubahan')
                    ->limit(5)
                    ->get()
                    ->map(fn ($item) => [
                        'id' => 'process-' . $item->id_riwayat_proses_laundry,
                        'type' => 'process',
                        'title' => 'Proses laundry '
                            . ($item->prosesLaundry?->detailPesanan?->pesanan?->kode_pesanan ?? '-')
                            . ' diperbarui',
                        'description' => $item->status_sebelumnya . ' → ' . $item->status_selanjutnya,
                        'user' => $item->user?->name,
                        'role' => $item->user?->roles->first()?->name,
                        'time' => $item->tanggal_perubahan?->toIso8601String(),
                    ])
            );

            $activities = $activities->concat(
                Pembayaran::with(['user.roles', 'pesanan'])
                    ->latest('tanggal_pembayaran')
                    ->limit(5)
                    ->get()
                    ->map(fn ($item) => [
                        'id' => 'payment-' . $item->id_pembayaran,
                        'type' => 'payment',
                        'title' => 'Pembayaran ' . ($item->pesanan?->kode_pesanan ?? '-') . ' dicatat',
                        'description' => $item->kode_pembayaran . ' · ' . $item->status_pembayaran,
                        'user' => $item->user?->name,
                        'role' => $item->user?->roles->first()?->name,
                        'time' => $item->tanggal_pembayaran?->toIso8601String(),
                    ])
            );
        }

        $deliveryHistory = RiwayatAntarJemput::with(['user.roles', 'antarJemput.pesanan']);

        if ($isCourier) {
            $deliveryHistory->whereHas(
                'antarJemput',
                fn ($query) => $query->where('id_kurir', $request->user()->id)
            );
        }

        $activities = $activities->concat(
            $deliveryHistory
                ->latest('tanggal_perubahan')
                ->limit(5)
                ->get()
                ->map(fn ($item) => [
                    'id' => 'delivery-' . $item->id_riwayat_antar_jemput,
                    'type' => 'delivery',
                    'title' => 'Tugas antar '
                        . ($item->antarJemput?->pesanan?->kode_pesanan ?? '-')
                        . ' diperbarui',
                    'description' => $item->status_sebelumnya . ' → ' . $item->status_selanjutnya,
                    'user' => $item->user?->name,
                    'role' => $item->user?->roles->first()?->name,
                    'time' => $item->tanggal_perubahan?->toIso8601String(),
                ])
        );

        return $activities
            ->filter(fn ($item) => $item['time'] !== null)
            ->sortByDesc(fn ($item) => Carbon::parse($item['time'])->timestamp)
            ->take(8)
            ->values()
            ->all();
    }
}
