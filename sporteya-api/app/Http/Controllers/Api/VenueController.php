<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Venue;
use App\Models\VenueBlockedSlot;
use App\Models\VenuePricing;
use App\Models\VenueReview;
use App\Models\VenueSchedule;
use App\Models\VenueSport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VenueController extends Controller
{
    /**
     * Search and list active venues with PostGIS distance calculation.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:500'], // in km, default 25
            'city' => ['nullable', 'string', 'max:100'],
            'sport_id' => ['nullable', 'uuid', 'exists:sports,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $page = (int) ($validated['page'] ?? 1);
        $radiusKm = (float) ($validated['radius'] ?? 25);
        $cacheKey = 'venues:search:' . md5(json_encode($request->all()));

        $result = Cache::remember($cacheKey, 300, function () use ($validated, $page, $radiusKm) {
            $offset = ($page - 1) * 15;
            $hasCoords = isset($validated['latitude']) && isset($validated['longitude']);

            if ($hasCoords) {
                $lng = (float) $validated['longitude'];
                $lat = (float) $validated['latitude'];
                $radiusMeters = $radiusKm * 1000;

                $query = "
                    SELECT v.id, v.owner_id, v.name, v.description, v.address, v.city, v.phone,
                           v.is_active, v.is_verified, v.has_lighting, v.has_changing_room, v.has_parking,
                           v.created_at, v.updated_at,
                           ROUND((ST_Distance(v.location, ST_MakePoint(:lng, :lat)::geography) / 1000)::numeric, 2) AS distance_km
                    FROM venues v
                    WHERE v.is_active = true
                      AND ST_DWithin(v.location, ST_MakePoint(:lng, :lat)::geography, :radius)
                ";
                $bindings = [
                    'lng' => $lng,
                    'lat' => $lat,
                    'radius' => $radiusMeters,
                ];

                if (!empty($validated['sport_id'])) {
                    $query .= " AND EXISTS (SELECT 1 FROM venue_sports vs WHERE vs.venue_id = v.id AND vs.sport_id = :sport_id)";
                    $bindings['sport_id'] = $validated['sport_id'];
                }

                $query .= " ORDER BY distance_km ASC LIMIT 15 OFFSET :offset";
                $bindings['offset'] = $offset;

                $venuesData = DB::select($query, $bindings);
            } else {
                $query = Venue::where('is_active', true);

                if (!empty($validated['city'])) {
                    $query->where('city', $validated['city']);
                }

                if (!empty($validated['sport_id'])) {
                    $query->whereHas('sports', function ($q) use ($validated) {
                        $q->where('sport_id', $validated['sport_id']);
                    });
                }

                $venuesData = $query->skip($offset)->take(15)->get();
            }

            // Hydrate relations and average rating
            $venueIds = collect($venuesData)->pluck('id')->all();
            $venues = Venue::whereIn('id', $venueIds)
                ->with(['sports.sport', 'media'])
                ->get()
                ->keyBy('id');

            // Attach distance and compute ratings
            return collect($venuesData)->map(function ($item) use ($venues) {
                $id = is_object($item) ? $item->id : $item['id'];
                $venueModel = $venues->get($id);
                if (!$venueModel) return null;

                $avgRating = VenueReview::where('venue_id', $id)->avg('rating');
                $reviewsCount = VenueReview::where('venue_id', $id)->count();

                $venueArray = $venueModel->toArray();
                $venueArray['distance_km'] = is_object($item) && isset($item->distance_km) ? (float) $item->distance_km : null;
                $venueArray['avg_rating'] = $avgRating ? round((float) $avgRating, 1) : null;
                $venueArray['reviews_count'] = $reviewsCount;

                return $venueArray;
            })->filter()->values();
        });

        return response()->json(['venues' => $result, 'page' => $page], 200);
    }

    /**
     * Get full venue details (cached 10 min).
     */
    public function show(string $id): JsonResponse
    {
        $venue = Cache::remember("venue:{$id}", 600, function () use ($id) {
            $venueModel = Venue::with([
                'sports.sport',
                'media',
                'pricing.sport',
                'schedules',
                'cancellationPolicies',
            ])->findOrFail($id);

            $avgRating = VenueReview::where('venue_id', $id)->avg('rating');
            $reviewsCount = VenueReview::where('venue_id', $id)->count();

            $venueArray = $venueModel->toArray();
            $venueArray['avg_rating'] = $avgRating ? round((float) $avgRating, 1) : null;
            $venueArray['reviews_count'] = $reviewsCount;

            return $venueArray;
        });

        return response()->json(['venue' => $venue], 200);
    }

    /**
     * Create a new venue during owner onboarding (Correction 2B & B).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:20'],
            'has_lighting' => ['nullable', 'boolean'],
            'has_changing_room' => ['nullable', 'boolean'],
            'has_parking' => ['nullable', 'boolean'],
            'sports' => ['required', 'array', 'min:1'],
            'sports.*.sport_id' => ['required', 'uuid', 'exists:sports,id'],
            'sports.*.court_count' => ['required', 'integer', 'min:1'],
            'sports.*.players_per_team' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();

        $venue = DB::transaction(function () use ($user, $validated) {
            $venue = Venue::create([
                'owner_id' => $user->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'address' => $validated['address'],
                'city' => $validated['city'],
                'phone' => $validated['phone'] ?? null,
                'has_lighting' => $validated['has_lighting'] ?? false,
                'has_changing_room' => $validated['has_changing_room'] ?? false,
                'has_parking' => $validated['has_parking'] ?? false,
                'is_verified' => false,
                'is_active' => false,
                'location' => DB::raw("ST_MakePoint({$validated['longitude']}, {$validated['latitude']})::geography"),
            ]);

            // Set PostGIS location
            DB::statement(
                "UPDATE venues SET location = ST_MakePoint(?, ?)::geography WHERE id = ?",
                [$validated['longitude'], $validated['latitude'], $venue->id]
            );

            // Bulk insert venue_sports
            foreach ($validated['sports'] as $sportItem) {
                VenueSport::create([
                    'venue_id' => $venue->id,
                    'sport_id' => $sportItem['sport_id'],
                    'court_count' => $sportItem['court_count'],
                    'players_per_team' => $sportItem['players_per_team'],
                ]);
            }

            // Correction B: Mark setup_complete = true for venue owner in same transaction
            $user->update(['setup_complete' => true]);

            return $venue;
        });

        return response()->json([
            'venue' => $venue->fresh()->load('sports.sport'),
            'setup_complete' => true,
        ], 201);
    }

    /**
     * Compute slot availability for a venue on a given date.
     */
    public function availability(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'sport_id' => ['nullable', 'uuid', 'exists:sports,id'],
        ]);

        $date = $request->input('date', Carbon::today()->toDateString());
        $sportId = $request->input('sport_id');

        $venue = Venue::findOrFail($id);
        $dayOfWeek = Carbon::parse($date)->dayOfWeek; // 0 = Sunday ... 6 = Saturday

        $schedule = VenueSchedule::where('venue_id', $venue->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_closed', false)
            ->first();

        if (!$schedule) {
            return response()->json(['date' => $date, 'slots' => []], 200);
        }

        // Generate base slots based on schedule
        $duration = $schedule->slot_duration_minutes ?: 60;
        $open = Carbon::parse("{$date} {$schedule->open_time}");
        $close = Carbon::parse("{$date} {$schedule->close_time}");

        // If closing past midnight
        if ($close->lte($open)) {
            $close->addDay();
        }

        $allSlots = [];
        $cursor = $open->copy();

        while ($cursor->copy()->addMinutes($duration)->lte($close)) {
            $slotStart = $cursor->format('H:i:s');
            $slotEnd = $cursor->copy()->addMinutes($duration)->format('H:i:s');

            $allSlots[] = [
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
            ];

            $cursor->addMinutes($duration);
        }

        // Fetch blocked slots for this date
        $blockedSlots = VenueBlockedSlot::where('venue_id', $venue->id)
            ->where('date', $date)
            ->get();

        // Fetch confirmed bookings overlapping this date
        $confirmedBookings = DB::table('bookings')
            ->join('match_lobbies', 'match_lobbies.id', '=', 'bookings.lobby_id')
            ->where('bookings.venue_id', $venue->id)
            ->where('bookings.status', 'confirmed')
            ->where('match_lobbies.match_date', $date)
            ->select('match_lobbies.start_time', 'match_lobbies.end_time')
            ->get();

        // Fetch pricing rules for this venue and day
        $pricingRules = VenuePricing::where('venue_id', $venue->id)
            ->where('day_of_week', $dayOfWeek)
            ->when($sportId, fn($q) => $q->where('sport_id', $sportId))
            ->get();

        $availableSlots = [];

        foreach ($allSlots as $slot) {
            $start = $slot['start_time'];
            $end = $slot['end_time'];

            // Check blocked slots collision
            $isBlocked = $blockedSlots->contains(function ($blocked) use ($start, $end) {
                return ($start < $blocked->end_time && $end > $blocked->start_time);
            });

            if ($isBlocked) {
                continue;
            }

            // Check confirmed bookings collision
            $isBooked = $confirmedBookings->contains(function ($booking) use ($start, $end) {
                return ($start < $booking->end_time && $end > $booking->start_time);
            });

            if ($isBooked) {
                continue;
            }

            // Find matching price
            $matchedPricing = $pricingRules->first(function ($rule) use ($start, $end) {
                return ($start >= $rule->start_time && $end <= $rule->end_time);
            });

            $availableSlots[] = [
                'start_time' => $start,
                'end_time' => $end,
                'price_per_hour' => $matchedPricing ? (float) $matchedPricing->price_per_hour : null,
                'currency' => $matchedPricing ? $matchedPricing->currency : 'EGP',
            ];
        }

        return response()->json([
            'date' => $date,
            'slots' => $availableSlots,
        ], 200);
    }

    /**
     * Get paginated reviews for a venue.
     */
    public function reviews(string $id): JsonResponse
    {
        $reviews = VenueReview::where('venue_id', $id)
            ->with('player.user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json(['reviews' => $reviews], 200);
    }
}
