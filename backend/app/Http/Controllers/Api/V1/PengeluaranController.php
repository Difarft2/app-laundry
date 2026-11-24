<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PengeluaranController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Pengeluaran::with('user');

        $query->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->string('search')->toString();
            $query->where(function ($query) use ($search) {
                $query->where('kode_pengeluaran', 'like', "%{$search}%")
                    ->orWhere('kategori', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        });

        $query->when($request->filled('kategori'), function ($query) use ($request) {
            $query->where('kategori', $request->kategori);
        });

        $query->when($request->filled('tanggal_mulai'), function ($query) use ($request) {
            $query->whereDate('tanggal', '>=', $request->tanggal_mulai);
        });

        $query->when($request->filled('tanggal_selesai'), function ($query) use ($request) {
            $query->whereDate('tanggal', '<=', $request->tanggal_selesai);
        });

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return response()->json(
            $query->latest('tanggal')->latest('id_pengeluaran')->paginate($perPage)->withQueryString()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'tanggal' => ['required', 'date'],
        ]);

        $pengeluaran = Pengeluaran::create([
            'kode_pengeluaran' => $this->generateCode(),
            'kategori' => $validated['kategori'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'jumlah' => $validated['jumlah'],
            'tanggal' => $validated['tanggal'],
            'id_user' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil dicatat.',
            'data' => $pengeluaran->load('user'),
        ], 201);
    }

    public function update(Request $request, Pengeluaran $pengeluaran): JsonResponse
    {
        $validated = $request->validate([
            'kategori' => ['sometimes', 'required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'jumlah' => ['sometimes', 'required', 'numeric', 'min:0'],
            'tanggal' => ['sometimes', 'required', 'date'],
        ]);

        $pengeluaran->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil diperbarui.',
            'data' => $pengeluaran->load('user'),
        ]);
    }

    public function destroy(Pengeluaran $pengeluaran): JsonResponse
    {
        $pengeluaran->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil dihapus.',
        ]);
    }

    private function generateCode(): string
    {
        do {
            $code = 'PGL-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
        } while (Pengeluaran::where('kode_pengeluaran', $code)->exists());

        return $code;
    }
}
