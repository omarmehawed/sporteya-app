<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\MatchParticipant;
use App\Models\Venue;
use App\Models\VenueReview;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Submit a review for a completed venue booking.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'uuid', 'exists:bookings,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = $request->user()->playerProfile;
        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $booking = Booking::with('lobby')->findOrFail($validated['booking_id']);

        if ($booking->status !== 'completed') {
            return response()->json(['message' => 'يمكن التقييم فقط بعد اكتمال الحضور في المباراة'], 422);
        }

        // Verify player was an accepted participant
        $isParticipant = MatchParticipant::where('lobby_id', $booking->lobby_id)
            ->where('player_id', $profile->id)
            ->where('invite_status', 'accepted')
            ->exists();

        if (!$isParticipant) {
            return response()->json(['message' => 'غير مصرح لك بتقييم هذه المباراة لأنك لم تكن مشاركاً'], 403);
        }

        // Verify within 48h window
        $dateStr = Carbon::parse($booking->lobby->match_date)->format('Y-m-d');
        $matchEnd = Carbon::parse("{$dateStr} {$booking->lobby->end_time}");
        if (now()->diffInHours($matchEnd, false) < -48) {
            return response()->json(['message' => 'انتهت الفترة المتاحة للتقييم (48 ساعة بعد المباراة)'], 422);
        }

        // Check if already reviewed for this booking by this player
        $alreadyReviewed = VenueReview::where('booking_id', $booking->id)
            ->where('player_id', $profile->id)
            ->exists();

        if ($alreadyReviewed) {
            return response()->json(['message' => 'لقد قمت بتقييم هذا الحجز بالفعل'], 422);
        }

        $review = DB::transaction(function () use ($validated, $booking, $profile) {
            $review = VenueReview::create([
                'booking_id' => $booking->id,
                'venue_id' => $booking->venue_id,
                'player_id' => $profile->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]);

            // Update venue average rating and reviews count
            $venue = Venue::find($booking->venue_id);
            if ($venue) {
                $avgRating = VenueReview::where('venue_id', $venue->id)->avg('rating');
                $count = VenueReview::where('venue_id', $venue->id)->count();

                $venue->update([
                    'rating_avg' => round((float) $avgRating, 2),
                    'reviews_count' => $count,
                ]);

                Cache::forget("venue:{$venue->id}");
            }

            return $review;
        });

        return response()->json(['review' => $review], 201);
    }
}
