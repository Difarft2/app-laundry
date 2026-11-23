<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Delivery\AssignCourierRequest;
use App\Http\Requests\Api\V1\Delivery\StoreDeliveryTaskRequest;
use App\Http\Requests\Api\V1\Delivery\UpdateDeliveryStatusRequest;
use App\Http\Resources\Api\V1\DeliveryTaskResource;
use App\Models\AntarJemput;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeliveryTaskController extends Controller
{
    private const TRANSITIONS = [
        'menunggu' => ['ditugaskan', 'gagal'],
        'ditugaskan' => ['menuju_lokasi', 'gagal'],
        'menuju_lokasi' => ['barang_diambil', 'barang_diantar', 'gagal'],
        'barang_diambil' => ['selesai', 'gagal'],
        'barang_diantar' => ['selesai', 'gagal'],
        'selesai' => [],
        'gagal' => [],
    ];

    private function relations(): array
    {
        return [
            'pesanan',
            'kurir',
            'riwayatAntarJemput' => fn ($query) => $query
                ->with('user')
                ->latest('tanggal_perubahan'),
        ];
    }

    private function loadRelations(AntarJemput $task): AntarJemput
    {
        return $task->load($this->relations());
    }

    private function scopeForUser(Request $request, $query)
    {
        if ($request->user()->hasRole('kurir')) {
            $query->where('id_kurir', $request->user()->id);
        }

        return $query;
    }

    private function ensureCanAccess(Request $request, AntarJemput $task): void
    {
        abort_if(
            $request->user()->hasRole('kurir')
                && (int) $task->id_kurir !== (int) $request->user()->id,
            403,
            'Anda hanya dapat mengakses tugas milik Anda.'
        );
    }

    public function index(Request $request)
    {
        $query = $this->scopeForUser($request, AntarJemput::with($this->relations()));

        $query->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->input('search');

            $query->where(function ($query) use ($search) {
                $query->where('alamat_tujuan', 'like', "%{$search}%")
                    ->orWhereHas('pesanan', function ($query) use ($search) {
                        $query->where('kode_pesanan', 'like', "%{$search}%")
                            ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                            ->orWhere('no_hp', 'like', "%{$search}%");
                    });
            });
        });

        $query->when($request->filled('status'), function ($query) use ($request) {
            $query->where('status_antar_jemput', $request->input('status'));
        });

        $query->when($request->filled('jenis'), function ($query) use ($request) {
            $query->where('jenis_antar_jemput', $request->input('jenis'));
        });

        $query->when($request->filled('id_kurir'), function ($query) use ($request) {
            $query->where('id_kurir', $request->input('id_kurir'));
        });

        if ($request->boolean('unassigned')) {
            $query->whereNull('id_kurir');
        }

        if ($request->boolean('assigned')) {
            $query->whereNotNull('id_kurir');
        }

        $query->when($request->filled('tanggal_mulai'), function ($query) use ($request) {
            $query->whereDate('jadwal', '>=', $request->input('tanggal_mulai'));
        });

        $query->when($request->filled('tanggal_selesai'), function ($query) use ($request) {
            $query->whereDate('jadwal', '<=', $request->input('tanggal_selesai'));
        });

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return DeliveryTaskResource::collection(
            $query->latest('id_antar_jemput')->paginate($perPage)->withQueryString()
        );
    }

    public function show(Request $request, AntarJemput $deliveryTask): DeliveryTaskResource
    {
        $this->ensureCanAccess($request, $deliveryTask);

        return new DeliveryTaskResource($this->loadRelations($deliveryTask));
    }

    public function store(StoreDeliveryTaskRequest $request)
    {
        $validated = $request->validated();

        $status = empty($validated['id_kurir']) ? 'menunggu' : 'ditugaskan';

        $task = DB::transaction(function () use ($validated, $status) {
            $task = AntarJemput::create([
                'id_pesanan' => $validated['id_pesanan'],
                'id_kurir' => $validated['id_kurir'] ?? null,
                'jenis_antar_jemput' => $validated['jenis_antar_jemput'],
                'status_antar_jemput' => $status,
                'alamat_tujuan' => $validated['alamat_tujuan'],
                'nama_penerima' => $validated['nama_penerima'] ?? null,
                'no_hp_penerima' => $validated['no_hp_penerima'] ?? null,
                'jadwal' => $validated['jadwal'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $task->riwayatAntarJemput()->create([
                'id_user' => Auth::id(),
                'status_sebelumnya' => null,
                'status_selanjutnya' => $status,
                'keterangan' => 'Permintaan antar-jemput dibuat.',
                'tanggal_perubahan' => now(),
            ]);

            return $task;
        });

        return (new DeliveryTaskResource($this->loadRelations($task)))
            ->response()
            ->setStatusCode(201);
    }

    public function assign(AssignCourierRequest $request, AntarJemput $deliveryTask): JsonResponse
    {
        $newCourier = $request->validated('id_kurir');

        if ($newCourier !== null && (int) $deliveryTask->id_kurir === (int) $newCourier) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas sudah ditugaskan ke kurir tersebut.',
            ], 422);
        }

        if (
            !in_array($deliveryTask->status_antar_jemput, ['menunggu', 'ditugaskan'], true)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Kurir tidak dapat diubah karena tugas sudah berjalan atau selesai.',
            ], 422);
        }

        $statusLama = $deliveryTask->status_antar_jemput;
        $statusBaru = $newCourier !== null ? 'ditugaskan' : 'menunggu';

        $kurir = $newCourier !== null ? User::find($newCourier) : null;
        $keterangan = $kurir
            ? "Kurir ditugaskan: {$kurir->name}."
            : 'Penugasan kurir dibatalkan.';

        DB::transaction(function () use (
            $deliveryTask,
            $newCourier,
            $statusLama,
            $statusBaru,
            $keterangan
        ) {
            $deliveryTask->update([
                'id_kurir' => $newCourier,
                'status_antar_jemput' => $statusBaru,
            ]);

            $deliveryTask->riwayatAntarJemput()->create([
                'id_user' => Auth::id(),
                'status_sebelumnya' => $statusLama,
                'status_selanjutnya' => $statusBaru,
                'keterangan' => $keterangan,
                'tanggal_perubahan' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Penugasan kurir berhasil diperbarui.',
            'data' => new DeliveryTaskResource($this->loadRelations($deliveryTask->refresh())),
        ]);
    }

    public function updateStatus(
        UpdateDeliveryStatusRequest $request,
        AntarJemput $deliveryTask
    ): JsonResponse {
        $this->ensureCanAccess($request, $deliveryTask);

        $validated = $request->validated();
        $statusBaru = $validated['status_antar_jemput'];
        $statusLama = $deliveryTask->status_antar_jemput;

        if ($statusLama === $statusBaru) {
            return response()->json([
                'success' => false,
                'message' => 'Status tugas sudah menggunakan status tersebut.',
            ], 422);
        }

        if (!in_array($statusBaru, self::TRANSITIONS[$statusLama] ?? [], true)) {
            return response()->json([
                'success' => false,
                'message' => "Status tidak dapat diubah dari {$statusLama} ke {$statusBaru}.",
            ], 422);
        }

        if ($statusBaru === 'ditugaskan' && $deliveryTask->id_kurir === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tugaskan kurir terlebih dahulu.',
            ], 422);
        }

        if (
            $statusBaru === 'barang_diambil'
            && $deliveryTask->jenis_antar_jemput !== 'penjemputan'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Status barang diambil hanya untuk tugas penjemputan.',
            ], 422);
        }

        if (
            $statusBaru === 'barang_diantar'
            && $deliveryTask->jenis_antar_jemput !== 'pengantaran'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Status barang diantar hanya untuk tugas pengantaran.',
            ], 422);
        }

        DB::transaction(function () use ($deliveryTask, $validated, $statusLama, $statusBaru) {
            $deliveryTask->update([
                'status_antar_jemput' => $statusBaru,
                'tanggal_selesai' => $statusBaru === 'selesai'
                    ? now()
                    : $deliveryTask->tanggal_selesai,
            ]);

            $deliveryTask->riwayatAntarJemput()->create([
                'id_user' => Auth::id(),
                'status_sebelumnya' => $statusLama,
                'status_selanjutnya' => $statusBaru,
                'keterangan' => $validated['keterangan'] ?? null,
                'tanggal_perubahan' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Status tugas berhasil diperbarui.',
            'data' => new DeliveryTaskResource($this->loadRelations($deliveryTask->refresh())),
        ]);
    }

    public function couriers(): JsonResponse
    {
        $couriers = User::role('kurir')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'no_hp'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'no_hp' => $user->no_hp,
            ]);

        return response()->json(['data' => $couriers]);
    }
}
