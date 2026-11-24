<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Setting\StoreLogoRequest;
use App\Http\Requests\Api\V1\Setting\UpdateSettingRequest;
use App\Http\Resources\Api\V1\SettingResource;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    /**
     * Pengaturan global adalah single row. GET sengaja terbuka agar branding
     * (nama, logo, versi, nomor) bisa dipakai halaman masuk dan dashboard.
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new SettingResource($this->setting()),
        ]);
    }

    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $setting = $this->setting();
        $validated = $request->validated();

        if (array_key_exists('logo_path', $validated)) {
            $this->deleteLogoFile($setting->logo_path, $validated['logo_path']);
        }

        $setting->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan aplikasi berhasil diperbarui.',
            'data' => new SettingResource($setting->refresh()),
        ]);
    }

    public function uploadLogo(StoreLogoRequest $request): JsonResponse
    {
        $setting = $this->setting();
        $file = $request->file('logo');

        $directory = public_path('uploads/logo');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = 'logo-'.$setting->id_setting.'-'.Str::random(8).'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        $path = '/uploads/logo/'.$filename;
        $this->deleteLogoFile($setting->logo_path, $path);
        $setting->update(['logo_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Logo aplikasi berhasil diunggah.',
            'data' => new SettingResource($setting->refresh()),
        ]);
    }

    private function setting(): AppSetting
    {
        return AppSetting::firstOrCreate(
            ['id_setting' => 1],
            AppSetting::DEFAULTS
        );
    }

    private function deleteLogoFile(?string $currentPath, ?string $nextPath): void
    {
        if (!$currentPath || $currentPath === $nextPath) {
            return;
        }

        if (!str_starts_with($currentPath, '/uploads/logo/')) {
            return;
        }

        $absolute = public_path(ltrim($currentPath, '/'));
        if (is_file($absolute)) {
            unlink($absolute);
        }
    }
}
