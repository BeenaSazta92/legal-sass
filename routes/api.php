<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController as V1AuthController;
use App\Http\Controllers\Api\V1\LawFirmController as V1LawFirmController;
use App\Http\Controllers\Api\V1\SubscriptionController as V1SubscriptionController;

Route::prefix('v1')->group(function () {
    // Public routes - Auth
    Route::post('/auth/register', [V1AuthController::class, 'register']);
    Route::post('/auth/login', [V1AuthController::class, 'login']);

    // Protected routes - require authentication
    Route::middleware('auth:sanctum')->group(function () {
        // Auth routes
        Route::get('/auth/me', [V1AuthController::class, 'me']);
        Route::post('/auth/logout', [V1AuthController::class, 'logout']);

        // Subscription routes (System Admin only)
        Route::prefix('subscriptions')->group(function () {
            Route::get('/', [V1SubscriptionController::class, 'index']);
            Route::post('/', [V1SubscriptionController::class, 'store']);
            Route::get('/{subscription}', [V1SubscriptionController::class, 'show']);
            Route::put('/{subscription}', [V1SubscriptionController::class, 'update']);
            Route::delete('/{subscription}', [V1SubscriptionController::class, 'destroy']);
            Route::post('/{subscription}/set-default', [V1SubscriptionController::class, 'setAsDefault']);
            Route::get('/default', [V1SubscriptionController::class, 'getDefault']);
        });

        // User routes (Platform Admin / Firm Admin)
        Route::prefix('users')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\UserController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\UserController::class, 'store']);
        });

        // Law Firm routes (System Admin only)
        Route::prefix('firms')->group(function () {
            Route::get('/', [V1LawFirmController::class, 'index']);
            Route::post('/', [V1LawFirmController::class, 'store']);
            Route::get('/{firm}', [V1LawFirmController::class, 'show']);
            Route::put('/{firm}', [V1LawFirmController::class, 'update']);
            Route::delete('/{firm}', [V1LawFirmController::class, 'destroy']);
            // Soft delete management routes
            Route::get('/trashed', [V1LawFirmController::class, 'trashed']);
            Route::post('/{firmId}/restore', [V1LawFirmController::class, 'restore']);
            Route::delete('/{firmId}/force-delete', [V1LawFirmController::class, 'forceDelete']);
        });
    });
});
