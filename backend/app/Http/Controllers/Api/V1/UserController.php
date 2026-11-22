<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\ResetUserPasswordRequest;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserStatusRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Menampilkan daftar pengguna.
     */
    public function index(Request $request)
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('no_hp', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->input('role'));
                });
            })
            ->latest()
            ->paginate(15);

        return UserResource::collection($users);
    }

    /**
     * Menambahkan pengguna baru.
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $roles = $data['roles'];

        unset($data['roles']);

        $this->ensureCanAssignOwner($request, $roles);

        $user = DB::transaction(function () use ($data, $roles, $request) {
            if ($request->user()->hasRole('owner')) {
                $data['status'] = 'pending';
            }

            $user = User::create($data);

            $user->syncRoles($roles);

            return $user;
        });

        return UserResource::make($user->load('roles'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Menampilkan detail pengguna.
     */
    public function show(User $user)
    {
        $user->load('roles');

        return UserResource::make($user);
    }

    /**
     * Mengubah data pengguna.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        $roles = $data['roles'] ?? null;

        unset($data['roles']);

        if ($roles !== null) {
            $this->ensureCanAssignOwner($request, $roles);
            $this->ensureNotLastOwner($user, $roles);
        }

        DB::transaction(function () use ($user, $data, $roles) {
            $user->update($data);

            if ($roles !== null) {
                $user->syncRoles($roles);
            }
        });

        return UserResource::make($user->fresh()->load('roles'));
    }

    /**
     * Menghapus pengguna.
     */
    public function destroy(Request $request, User $user)
    {
        abort_if(
            $request->user()->id === $user->id,
            422,
            'Anda tidak dapat menghapus akun sendiri.'
        );

        $this->ensureNotLastOwner($user);

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();

            $user->delete();
        });

        return response()->json([
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    /**
     * Reset password pengguna.
     */
    public function resetPassword(
        ResetUserPasswordRequest $request,
        User $user
    ) {
        $user->update([
            'password' => $request->validated('password'),
        ]);

        // Cabut seluruh token milik pengguna.
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password pengguna berhasil direset.',
        ]);
    }

    /**
     * Mengubah status akun pengguna (aktif, menunggu, ditolak).
     */
    public function updateStatus(
        UpdateUserStatusRequest $request,
        User $user
    ) {
        $status = $request->validated('status');

        abort_if(
            $request->user()->id === $user->id,
            422,
            'Anda tidak dapat mengubah status akun sendiri.'
        );

        $this->ensureNotLastActiveOwner($user, $status);

        if ($user->status === $status) {
            return response()->json([
                'message' => 'Status akun tidak berubah.',
                'data' => UserResource::make($user->load('roles')),
            ]);
        }

        DB::transaction(function () use ($user, $status) {
            $user->update(['status' => $status]);

            // Akun non-aktif langsung kehilangan seluruh sesi yang berjalan.
            if ($status !== 'active') {
                $user->tokens()->delete();
            }
        });

        $messages = [
            'active' => "Akun {$user->name} berhasil diaktifkan.",
            'pending' => "Akun {$user->name} diubah menjadi menunggu persetujuan.",
            'rejected' => "Akun {$user->name} ditolak.",
        ];

        return response()->json([
            'message' => $messages[$status] ?? 'Status akun berhasil diperbarui.',
            'data' => UserResource::make($user->fresh()->load('roles')),
        ]);
    }

    /**
     * Menyetujui pendaftaran pengguna.
     */
    public function approve(User $user)
    {
        abort_if(
            $user->status === 'active',
            422,
            'Pengguna ini sudah aktif.'
        );

        $user->update(['status' => 'active']);

        return response()->json([
            'message' => "Pengguna {$user->name} berhasil disetujui.",
            'data' => UserResource::make($user),
        ]);
    }

    /**
     * Mencegah status owner aktif terakhir diubah menjadi non-aktif.
     */
    private function ensureNotLastActiveOwner(
        User $user,
        string $newStatus
    ): void {
        if ($newStatus === 'active' || !$user->hasRole('owner')) {
            return;
        }

        $activeOwnerCount = User::role('owner')
            ->where('status', 'active')
            ->count();

        abort_if(
            $activeOwnerCount <= 1,
            422,
            'Status owner aktif terakhir tidak dapat diubah.'
        );
    }

    /**
     * Hanya owner yang boleh memberikan role owner.
     */
    private function ensureCanAssignOwner(Request $request, array $roles): void
    {
        if (in_array('owner', $roles, true)) {
            abort_unless(
                $request->user()->hasRole('owner'),
                403,
                'Hanya owner yang dapat memberikan role owner.'
            );
        }
    }

    /**
     * Mencegah penghapusan atau penurunan role owner terakhir.
     */
    private function ensureNotLastOwner(
        User $user,
        ?array $newRoles = null
    ): void {
        if (!$user->hasRole('owner')) {
            return;
        }

        if ($newRoles !== null && in_array('owner', $newRoles, true)) {
            return;
        }

        $ownerCount = User::role('owner')->count();

        abort_if(
            $ownerCount <= 1,
            422,
            'Owner terakhir tidak dapat dihapus atau diturunkan rolenya.'
        );
    }
}
