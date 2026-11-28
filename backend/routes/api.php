<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\ServiceTimeController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\DeliveryTaskController;
use App\Http\Controllers\Api\V1\PengeluaranController;
use App\Http\Controllers\Api\V1\LandingDeliveryController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\LandingTrackController;
use App\Http\Controllers\Api\V1\RolePermissionController;

Route::prefix('v1')->group(function () {

    // Public authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');
    });

    // Landing page tidak punya login; akses dilindungi kunci rahasia bersama.
    Route::post('/landing/delivery-requests', [LandingDeliveryController::class, 'store'])
        ->middleware(['landing.key', 'throttle:10,1']);

    Route::get('/landing/track/{kode_pesanan}', [LandingTrackController::class, 'show'])
        ->middleware(['landing.key', 'throttle:30,1']);

    // Branding aplikasi bersifat publik agar bisa dipakai halaman masuk.
    Route::get('/settings', [SettingController::class, 'show']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        // Role-aware dashboard data
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:dashboard.view');

        // Authentication
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::put('/password', [AuthController::class, 'updatePassword']);
        });

        // Pengaturan global: hanya admin yang dapat mengubah.
        Route::middleware('permission:settings.manage')->group(function () {
            Route::put('/settings', [SettingController::class, 'update']);
            Route::post('/settings/logo', [SettingController::class, 'uploadLogo']);
        });

        // User Management
        Route::middleware('permission:users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{user}', [UserController::class, 'show']);
        });

        Route::middleware('permission:users.create')->group(function () {
            Route::post('/users', [UserController::class, 'store']);
        });

        Route::middleware('permission:users.update')->group(function () {
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::patch('/users/{user}/status', [UserController::class, 'updateStatus']);
            Route::put('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        });

        Route::middleware('permission:users.delete')->group(function () {
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
        });

        Route::middleware('permission:users.approve')->group(function () {
            Route::patch('/users/{user}/approve', [UserController::class, 'approve']);
        });

        // Service Management
        Route::middleware('permission:services.view')->group(function () {
            Route::get('/services', [ServiceController::class, 'index']);
            Route::get('/services/{service}', [ServiceController::class, 'show']);
        });

        Route::middleware('permission:services.create')->group(function () {
            Route::post('/services', [ServiceController::class, 'store']);
        });

        Route::middleware('permission:services.update')->group(function () {
            Route::put('/services/{service}', [ServiceController::class, 'update']);
        });

        Route::middleware('permission:services.delete')->group(function () {
            Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
        });

        // Service Time Management
        Route::middleware('permission:service-times.view')->group(function () {
            Route::get('/service-times', [ServiceTimeController::class, 'index']);
            Route::get('/service-times/{service_time}', [ServiceTimeController::class, 'show']);
        });

        Route::middleware('permission:service-times.create')->group(function () {
            Route::post('/service-times', [ServiceTimeController::class, 'store']);
        });

        Route::middleware('permission:service-times.update')->group(function () {
            Route::put('/service-times/{service_time}', [ServiceTimeController::class, 'update']);
        });

        Route::middleware('permission:service-times.delete')->group(function () {
            Route::delete('/service-times/{service_time}', [ServiceTimeController::class, 'destroy']);
        });


        Route::prefix('payment-methods')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'index'])
                ->middleware('permission:payment-methods.view');

            Route::post('/', [PaymentMethodController::class, 'store'])
                ->middleware('permission:payment-methods.create');

            Route::get('/{paymentMethod}', [PaymentMethodController::class, 'show'])
                ->middleware('permission:payment-methods.view');

            Route::put('/{paymentMethod}', [PaymentMethodController::class, 'update'])
                ->middleware('permission:payment-methods.update');

            Route::patch('/{paymentMethod}/status', [PaymentMethodController::class, 'updateStatus'])
                ->middleware('permission:payment-methods.update');

            Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy'])
                ->middleware('permission:payment-methods.delete');
        });

        // Payment History & Recording
        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])
                ->middleware('permission:payments.view');

            Route::get('/{payment}', [PaymentController::class, 'show'])
                ->middleware('permission:payments.view');

            Route::post('/', [PaymentController::class, 'store'])
                ->middleware('permission:payments.create');

            Route::patch('/{payment}/verify', [PaymentController::class, 'verify'])
                ->middleware('permission:payments.verify');
        });

        // Expense Management
        Route::prefix('expenses')->group(function () {
            Route::get('/', [PengeluaranController::class, 'index'])
                ->middleware('permission:finance.view');

            Route::post('/', [PengeluaranController::class, 'store'])
                ->middleware('permission:finance.manage');

            Route::put('/{pengeluaran}', [PengeluaranController::class, 'update'])
                ->middleware('permission:finance.manage');

            Route::delete('/{pengeluaran}', [PengeluaranController::class, 'destroy'])
                ->middleware('permission:finance.manage');
        });

        // Order Management
        Route::prefix('orders')->group(function () {

            Route::get('/', [OrderController::class, 'index'])
                ->middleware('permission:orders.view');

            Route::post('/', [OrderController::class, 'store'])
                ->middleware('permission:orders.create');

            Route::get('/lookup', [OrderController::class, 'lookup'])
                ->middleware('permission:orders.view');

            Route::get('/{order}', [OrderController::class, 'show'])
                ->middleware('permission:orders.view');

            Route::get('/{order}/receipt', [OrderController::class, 'receipt'])
                ->middleware('permission:orders.view');

            Route::post('/{order}/delivery', [OrderController::class, 'createDelivery'])
                ->middleware('permission:orders.update-status');

            Route::put('/{order}', [OrderController::class, 'update'])
                ->middleware('permission:orders.update');

            Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])
                ->middleware('permission:orders.update-status');

            Route::delete('/{order}', [OrderController::class, 'destroy'])
                ->middleware('permission:orders.delete');
        });

        // Delivery (Antar-Jemput) Management
        Route::prefix('delivery-tasks')->group(function () {
            Route::get('/couriers', [DeliveryTaskController::class, 'couriers'])
                ->middleware('permission:delivery.assign');

            Route::get('/', [DeliveryTaskController::class, 'index'])
                ->middleware('permission:delivery.view');

            Route::post('/', [DeliveryTaskController::class, 'store'])
                ->middleware('permission:delivery.assign');

            Route::get('/{deliveryTask}', [DeliveryTaskController::class, 'show'])
                ->middleware('permission:delivery.view');

            Route::patch('/{deliveryTask}/verify', [DeliveryTaskController::class, 'verify'])
                ->middleware('permission:delivery.verify');

            Route::patch('/{deliveryTask}/reject', [DeliveryTaskController::class, 'reject'])
                ->middleware('permission:delivery.verify');

            Route::patch('/{deliveryTask}/assign', [DeliveryTaskController::class, 'assign'])
                ->middleware('permission:delivery.assign');

            Route::patch('/{deliveryTask}/accept', [DeliveryTaskController::class, 'accept'])
                ->middleware('permission:delivery.update');

            Route::patch('/{deliveryTask}/status', [DeliveryTaskController::class, 'updateStatus'])
                ->middleware('permission:delivery.update');
        });
        // Role & Permission Management (Admin only)
        Route::middleware('permission:roles.manage')->group(function () {
            Route::get('/role-permission/{role?}', [RolePermissionController::class, 'index']);
            Route::put('/role-permission/{role?}', [RolePermissionController::class, 'update']);
        });

        Route::middleware('permission:roles.manage')->group(function () {
            Route::get('/user-permission/{user}', [RolePermissionController::class, 'showUserOverrides']);
            Route::put('/user-permission/{user}', [RolePermissionController::class, 'updateUser']);
        });
    });
});
