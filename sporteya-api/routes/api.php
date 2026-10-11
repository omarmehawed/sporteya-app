<?php

use App\Http\Controllers\Api\AdminVenueController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingGroupController;
use App\Http\Controllers\Api\CheckinController;
use App\Http\Controllers\Api\MatchLobbyController;
use App\Http\Controllers\Api\MatchResultController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SportController;
use App\Http\Controllers\Api\VenueController;
use App\Http\Controllers\Api\VenueOwnerDashboardController;
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

    // Public Lobbies
    Route::get('/lobbies/public', [MatchLobbyController::class, 'publicLobbies']);
    Route::get('/lobbies/join/{invite_code}', [MatchLobbyController::class, 'previewByInviteCode']);

    // Webhooks (no auth, HMAC verified inside controller)
    Route::post('/payments/callback', [PaymentController::class, 'callback']);

    // Protected Routes (auth:sanctum)
    Route::middleware('auth:sanctum')->group(function () {

        // Common Authenticated User Routes (Notifications, Bookings, Check-in)
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        });

        Route::prefix('bookings')->group(function () {
            Route::get('/', [PaymentController::class, 'index']);
            Route::get('/{id}', [PaymentController::class, 'show']);
            Route::post('/{id}/cancel', [PaymentController::class, 'cancelBooking']);
        });

        Route::get('/payments/status/{booking_id}', [PaymentController::class, 'status']);
        Route::post('/checkin/manual', [CheckinController::class, 'manual']);

        // Player Role Routes
        Route::middleware('role:player')->group(function () {
            // Onboarding (Phase B4)
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

            // Booking Groups (Phase B5)
            Route::prefix('booking-groups')->group(function () {
                Route::get('/', [BookingGroupController::class, 'index']);
                Route::post('/', [BookingGroupController::class, 'store']);
                Route::get('/{id}', [BookingGroupController::class, 'show']);
                Route::delete('/{id}', [BookingGroupController::class, 'destroy']);
                Route::post('/{id}/members', [BookingGroupController::class, 'addMember']);
                Route::delete('/{id}/members/{playerId}', [BookingGroupController::class, 'removeMember']);
            });

            // Match Lobbies (Phase B6)
            Route::prefix('lobbies')->group(function () {
                Route::post('/', [MatchLobbyController::class, 'store']);
                Route::get('/{id}', [MatchLobbyController::class, 'show']);
                Route::post('/{id}/join', [MatchLobbyController::class, 'join']);
                Route::post('/{id}/invite', [MatchLobbyController::class, 'invite']);
                Route::put('/{id}/participants/{participant_id}', [MatchLobbyController::class, 'respondInvite']);
                Route::put('/{id}/participants/{participant_id}/approve', [MatchLobbyController::class, 'approveParticipant']);
                Route::delete('/{id}', [MatchLobbyController::class, 'destroy']);
            });

            // Payments (Phase B7)
            Route::post('/payments/initiate', [PaymentController::class, 'initiate']);

            // Reviews & Match Results (Phase B8)
            Route::post('/reviews/venue', [ReviewController::class, 'store']);
            Route::post('/matches/result', [MatchResultController::class, 'store']);
        });

        // Venue Owner Role Routes (Phase B4 & B8 Dashboard)
        Route::middleware('role:venue_owner')->group(function () {
            Route::post('/venues', [VenueController::class, 'store']);
            Route::post('/checkin/scan', [CheckinController::class, 'scan']);

            Route::prefix('venue/my')->group(function () {
                Route::get('/', [VenueOwnerDashboardController::class, 'show']);
                Route::put('/', [VenueOwnerDashboardController::class, 'update']);
                Route::post('/media', [VenueOwnerDashboardController::class, 'uploadMedia']);
                Route::delete('/media/{id}', [VenueOwnerDashboardController::class, 'deleteMedia']);
                Route::put('/schedules', [VenueOwnerDashboardController::class, 'updateSchedules']);
                Route::put('/pricing', [VenueOwnerDashboardController::class, 'updatePricing']);
                Route::post('/block', [VenueOwnerDashboardController::class, 'blockSlot']);
                Route::delete('/block/{id}', [VenueOwnerDashboardController::class, 'deleteBlockSlot']);
                Route::put('/cancellation-policy', [VenueOwnerDashboardController::class, 'updateCancellationPolicy']);
                Route::get('/bookings', [VenueOwnerDashboardController::class, 'bookings']);
            });
        });

        // Admin Role Routes (Phase B4)
        Route::middleware('role:admin')->group(function () {
            Route::put('/admin/venues/{id}/verify', [AdminVenueController::class, 'verify']);
        });
    });

    // Public Player Profile & Availability Route (guarded against 'me')
    Route::get('/player/{username}', [PlayerController::class, 'show'])->where('username', '^(?!me$).*');
});
