<?php

use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\DashboardWebController;
use App\Http\Controllers\Web\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// Venue Owner Dashboard (role: venue_owner)
Route::middleware(['auth', 'role:venue_owner'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardWebController::class, 'index'])->name('index');

    Route::get('/availability', [DashboardWebController::class, 'availability'])->name('availability');
    Route::post('/schedules', [DashboardWebController::class, 'saveSchedules'])->name('schedules.save');
    Route::post('/slots/block', [DashboardWebController::class, 'saveBlockSlot'])->name('slots.block');
    Route::delete('/slots/{id}', [DashboardWebController::class, 'deleteBlockSlot'])->name('slots.delete');

    Route::get('/pricing', [DashboardWebController::class, 'pricing'])->name('pricing');
    Route::post('/pricing', [DashboardWebController::class, 'savePricing'])->name('pricing.save');
    Route::delete('/pricing/{id}', [DashboardWebController::class, 'deletePricing'])->name('pricing.delete');

    Route::get('/bookings', [DashboardWebController::class, 'bookings'])->name('bookings');

    Route::get('/settings', [DashboardWebController::class, 'settings'])->name('settings');
    Route::post('/settings', [DashboardWebController::class, 'updateSettings'])->name('settings.update');
    Route::post('/photos', [DashboardWebController::class, 'uploadPhoto'])->name('photos.upload');
    Route::delete('/photos/{id}', [DashboardWebController::class, 'deletePhoto'])->name('photos.delete');

    Route::get('/reviews', [DashboardWebController::class, 'reviews'])->name('reviews');
});

// Admin Panel (role: admin)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/venues', [AdminWebController::class, 'venues'])->name('venues');
    Route::get('/venues/{id}', [AdminWebController::class, 'venueDetail'])->name('venues.show');
    Route::post('/venues/{id}/verify', [AdminWebController::class, 'verifyVenue'])->name('venues.verify');
});
