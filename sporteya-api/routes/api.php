<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('/google', [AuthController::class, 'google']);

        // Protected Auth Routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Protected Routes (by role)
    Route::middleware('auth:sanctum')->group(function () {
        // Player routes
        Route::middleware('role:player')->group(function () {
            // Player-specific endpoints will be placed here
        });

        // Venue Owner routes
        Route::middleware('role:venue_owner')->group(function () {
            // Venue-owner endpoints will be placed here
        });

        // Admin routes
        Route::middleware('role:admin')->group(function () {
            // Admin-specific endpoints will be placed here
        });
    });
});
