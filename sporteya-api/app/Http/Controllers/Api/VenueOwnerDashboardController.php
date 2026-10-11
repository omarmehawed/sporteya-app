<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Venue;
use App\Models\VenueBlockedSlot;
use App\Models\VenueMedia;
use App\Models\VenuePricing;
use App\Models\VenueSchedule;
use App\Services\MediaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VenueOwnerDashboardController extends Controller
{
    /**
     * Get owner's primary venue summary & statistics.
     */
    public function show(Request $request): JsonResponse
    {
        $owner = $request->user();
        $venue = Venue::where('owner_id', $owner->id)
            ->with(['sports.sport', 'media', 'schedules', 'pricing.sport', 'cancellationPolicy'])
            ->first();

        if (!$venue) {
            return response()->json(['message' => 'لم يتم العثور على أي ملعب خاص بك'], 404);
        }

        $today = Carbon::today()->toDateString();
        $weekAgo = Carbon::today()->subDays(7)->toDateString();

        $todayBookings = Booking::where('venue_id', $venue->id)
            ->whereDate('created_at', $today)
            ->where('status', 'confirmed')
            ->count();

        $todayRevenue = Booking::where('venue_id', $venue->id)
            ->whereDate('created_at', $today)
            ->where('status', 'confirmed')
            ->sum('venue_amount');

        $weekRevenue = Booking::where('venue_id', $venue->id)
            ->whereDate('created_at', '>=', $weekAgo)
            ->where('status', 'confirmed')
            ->sum('venue_amount');

        return response()->json([
            'venue' => $venue,
            'stats' => [
                'today_bookings' => $todayBookings,
                'today_revenue' => (float) $todayRevenue,
                'week_revenue' => (float) $weekRevenue,
                'avg_rating' => (float) $venue->rating_avg,
                'reviews_count' => $venue->reviews_count,
            ],
        ], 200);
    }

    /**
     * Update venue basic information.
     */
    public function update(Request $request): JsonResponse
    {
        $owner = $request->user();
        $venue = Venue::where('owner_id', $owner->id)->firstOrFail();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'has_lighting' => ['nullable', 'boolean'],
            'has_changing_room' => ['nullable', 'boolean'],
            'has_parking' => ['nullable', 'boolean'],
        ]);

        $venue->update($validated);
        Cache::forget("venue:{$venue->id}");

        return response()->json(['venue' => $venue->fresh()], 200);
    }

    /**
     * Upload photo to venue media.
     */
    public function uploadMedia(Request $request, MediaService $mediaService): JsonResponse
    {
        $owner = $request->user();
        $venue = Venue::where('owner_id', $owner->id)->firstOrFail();

        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,png,webp,jpg', 'max:5120'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $url = $mediaService->uploadVenuePhoto($request->file('photo'));

        $media = VenueMedia::create([
            'venue_id' => $venue->id,
            'url' => $url,
            'type' => 'image',
            'sort_order' => $venue->media()->count() + 1,
        ]);

        Cache::forget("venue:{$venue->id}");

        return response()->json(['media' => $media], 201);
    }

    /**
     * Delete venue media photo.
     */
    public function deleteMedia(string $id, Request $request): JsonResponse
    {
        $owner = $request->user();
        $venue = Venue::where('owner_id', $owner->id)->firstOrFail();

        $media = VenueMedia::where('id', $id)->where('venue_id', $venue->id)->firstOrFail();
        $media->delete();

        Cache::forget("venue:{$venue->id}");

        return response()->json(['message' => 'تم حذف الصورة بنجاح'], 200);
    }

    /**
     * Update operating schedules.
     */
    public function updateSchedules(Request $request): JsonResponse
    {
        $owner = $request->user();
        $venue = Venue::where('owner_id', $owner->id)->firstOrFail();

        $validated = $request->validate([
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.open_time' => ['required', 'date_format:H:i,H:i:s'],
            'schedules.*.close_time' => ['required', 'date_format:H:i,H:i:s'],
            'schedules.*.slot_duration_minutes' => ['nullable', 'integer', 'min:30', 'max:180'],
            'schedules.*.is_closed' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($venue, $validated) {
            foreach ($validated['schedules'] as $item) {
                VenueSchedule::updateOrCreate(
                    [
                        'venue_id' => $venue->id,
                        'day_of_week' => $item['day_of_week'],
                    ],
                    [
                        'open_time' => $item['open_time'],
                        'close_time' => $item['close_time'],
                        'slot_duration_minutes' => $item['slot_duration_minutes'] ?? 60,
                        'is_closed' => $item['is_closed'] ?? false,
                    ]
                );
            }
        });

        Cache::forget("venue:{$venue->id}");

        return response()->json(['schedules' => $venue->schedules()->get()], 200);
    }

    /**
     * Update venue pricing rules.
     */
    public function updatePricing(Request $request): JsonResponse
    {
        $owner = $request->user();
        $venue = Venue::where('owner_id', $owner->id)->firstOrFail();

        $validated = $request->validate([
            'pricing' => ['required', 'array', 'min:1'],
            'pricing.*.sport_id' => ['required', 'uuid', 'exists:sports,id'],
            'pricing.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'pricing.*.start_time' => ['required', 'date_format:H:i,H:i:s'],
            'pricing.*.end_time' => ['required', 'date_format:H:i,H:i:s'],
            'pricing.*.price_per_hour' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($venue, $validated) {
            $venue->pricing()->delete();

            foreach ($validated['pricing'] as $item) {
                VenuePricing::create([
                    'venue_id' => $venue->id,
                    'sport_id' => $item['sport_id'],
                    'day_of_week' => $item['day_of_week'],
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                    'price_per_hour' => $item['price_per_hour'],
                    'currency' => 'EGP',
                ]);
            }
        });

        Cache::forget("venue:{$venue->id}");

        return response()->json(['pricing' => $venue->pricing()->get()], 200);
    }

    /**
     * Block a slot for maintenance or private booking.
     */
    public function blockSlot(Request $request): JsonResponse
    {
        $owner = $request->user();
        $venue = Venue::where('owner_id', $owner->id)->firstOrFail();

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time' => ['required', 'date_format:H:i,H:i:s', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $blocked = VenueBlockedSlot::create([
            'venue_id' => $venue->id,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'reason' => $validated['reason'] ?? null,
        ]);

        return response()->json(['blocked_slot' => $blocked], 201);
    }

    /**
     * Unblock slot.
     */
    public function deleteBlockSlot(string $id, Request $request): JsonResponse
    {
        $owner = $request->user();
        $venue = Venue::where('owner_id', $owner->id)->firstOrFail();

        $blocked = VenueBlockedSlot::where('id', $id)->where('venue_id', $venue->id)->firstOrFail();
        $blocked->delete();

        return response()->json(['message' => 'تم إلغاء حظر الموعد بنجاح'], 200);
    }

    /**
     * Update cancellation policy.
     */
    public function updateCancellationPolicy(Request $request): JsonResponse
    {
        $owner = $request->user();
        $venue = Venue::where('owner_id', $owner->id)->firstOrFail();

        $validated = $request->validate([
            'full_refund_hours' => ['required', 'integer', 'min:0'],
            'half_refund_hours' => ['required', 'integer', 'min:0'],
            'no_refund_hours' => ['required', 'integer', 'min:0'],
            'custom_rules' => ['nullable', 'string'],
        ]);

        $policy = CancellationPolicy::updateOrCreate(
            ['venue_id' => $venue->id],
            $validated
        );

        Cache::forget("venue:{$venue->id}");

        return response()->json(['cancellation_policy' => $policy], 200);
    }

    /**
     * List bookings for owner's venue.
     */
    public function bookings(Request $request): JsonResponse
    {
        $owner = $request->user();
        $venue = Venue::where('owner_id', $owner->id)->firstOrFail();

        $bookings = Booking::where('venue_id', $venue->id)
            ->with(['lobby.sport', 'lobby.creator.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json(['bookings' => $bookings], 200);
    }
}
