<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\ServiceTimeController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\DeliveryTaskController;

Route::prefix('v1')->group(function () {

    // Public authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');
    });

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        // Authentication
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::put('/password', [AuthController::class, 'updatePassword']);
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

        // Order Management
        Route::prefix('orders')->group(function () {

            Route::get('/', [OrderController::class, 'index'])
                ->middleware('permission:orders.view');

            Route::post('/', [OrderController::class, 'store'])
                ->middleware('permission:orders.create');

            Route::get('/{order}', [OrderController::class, 'show'])
                ->middleware('permission:orders.view');

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

            Route::patch('/{deliveryTask}/assign', [DeliveryTaskController::class, 'assign'])
                ->middleware('permission:delivery.assign');

            Route::patch('/{deliveryTask}/status', [DeliveryTaskController::class, 'updateStatus'])
                ->middleware('permission:delivery.update');
        });
    });
});
