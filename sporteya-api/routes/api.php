<?php

use App\Http\Controllers\Api\AdminVenueController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingGroupController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\SportController;
use App\Http\Controllers\Api\VenueController;
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

    // Public Discovery Routes
    Route::get('/sports', [SportController::class, 'index']);
    Route::get('/venues', [VenueController::class, 'index']);
    Route::get('/venues/{id}', [VenueController::class, 'show']);
    Route::get('/venues/{id}/availability', [VenueController::class, 'availability']);
    Route::get('/venues/{id}/reviews', [VenueController::class, 'reviews']);

    // Protected Routes (by role)
    Route::middleware('auth:sanctum')->group(function () {

        // Player routes (Onboarding & Player APIs)
        Route::middleware('role:player')->group(function () {
            // Onboarding Flow (Phase B4)
            Route::prefix('onboarding')->group(function () {
                Route::post('/sports', [OnboardingController::class, 'sports']);
                Route::post('/username', [OnboardingController::class, 'username']);
                Route::post('/location', [OnboardingController::class, 'location']);
                Route::post('/avatar', [OnboardingController::class, 'avatar']);
                Route::put('/complete', [OnboardingController::class, 'complete']);
            });

            // Player Profile Management (Phase B5)
            Route::prefix('player')->group(function () {
                Route::get('/me', [PlayerController::class, 'me']);
                Route::put('/me', [PlayerController::class, 'update']);
                Route::put('/me/avatar', [PlayerController::class, 'updateAvatar']);
                Route::put('/me/status', [PlayerController::class, 'updateStatus']);
            });

            // Booking Groups CRUD (Phase B5)
            Route::prefix('booking-groups')->group(function () {
                Route::get('/', [BookingGroupController::class, 'index']);
                Route::post('/', [BookingGroupController::class, 'store']);
                Route::get('/{id}', [BookingGroupController::class, 'show']);
                Route::delete('/{id}', [BookingGroupController::class, 'destroy']);
                Route::post('/{id}/members', [BookingGroupController::class, 'addMember']);
                Route::delete('/{id}/members/{playerId}', [BookingGroupController::class, 'removeMember']);
            });
        });

        // Venue Owner routes (Phase B4 & Venue Management)
        Route::middleware('role:venue_owner')->group(function () {
            Route::post('/venues', [VenueController::class, 'store']);
        });

        // Admin routes (Phase B4 Admin Management)
        Route::middleware('role:admin')->group(function () {
            Route::put('/admin/venues/{id}/verify', [AdminVenueController::class, 'verify']);
        });
    });

    // Public Player Profile & Availability Route (guarded against 'me')
    Route::get('/player/{username}', [PlayerController::class, 'show'])->where('username', '^(?!me$).*');
});
