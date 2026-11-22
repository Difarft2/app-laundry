<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\UserResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login user and generate access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])
            ->first();

        if (
            !$user ||
            !Hash::check($credentials['password'], $user->password)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password tidak valid.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => $user->status === 'rejected'
                    ? 'Akun Anda ditolak. Hubungi admin untuk informasi lebih lanjut.'
                    : 'Akun Anda masih menunggu persetujuan Admin.',
            ], 403);
        }

        $token = $user->createToken(
            $credentials['device_name'] ?? 'web-dashboard'
        )->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',

            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user),
            ],
        ], 200);
    }

    /**
     * Logout current user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Update authenticated user profile.
     */
    public function updateProfile(
        UpdateProfileRequest $request
    ): JsonResponse {
        $user = $request->user();

        $data = $request->validated();

        if ($user->email !== $data['email']) {
            $user->email_verified_at = null;
        }

        $user->fill($data);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * Update authenticated user password.
     */
    public function updatePassword(
        UpdatePasswordRequest $request
    ): JsonResponse {
        $user = $request->user();

        $user->password = $request->validated()['password'];
        $user->save();

        // Revoke all existing access tokens.
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui. Silakan login kembali.',
        ]);
    }
}
