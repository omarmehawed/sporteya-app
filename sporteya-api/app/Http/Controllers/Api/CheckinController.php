<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    /**
     * Venue owner scans player's QR code.
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_token' => ['required', 'string', 'size:64'],
        ]);

        $owner = $request->user();
        $venueIds = $owner->venues()->pluck('id');

        $booking = Booking::with('lobby')->where('qr_token', $validated['qr_token'])->first();

        if (!$booking) {
            return response()->json(['message' => 'رمز QR غير صالح'], 404);
        }

        if (!$venueIds->contains($booking->venue_id)) {
            return response()->json(['message' => 'هذا الحجز لا يتبع أي ملعب خاص بك'], 403);
        }

        if ($booking->status === 'completed') {
            return response()->json([
                'message' => 'تم تسجيل الحضور مسبقاً',
                'booking' => $booking,
            ], 200);
        }

        // Validate time window (start - 30 min to start + 60 min)
        $dateStr = Carbon::parse($booking->lobby->match_date)->format('Y-m-d');
        $matchStart = Carbon::parse("{$dateStr} {$booking->lobby->start_time}");
        $windowStart = $matchStart->copy()->subMinutes(30);
        $windowEnd = $matchStart->copy()->addMinutes(60);

        if (now()->lt($windowStart) || now()->gt($windowEnd)) {
            return response()->json([
                'message' => 'تسجيل الحضور متاح فقط قبل الموعد بنصف ساعة وحتى ساعة بعد البداية',
            ], 422);
        }

        $booking->update(['status' => 'completed']);

        return response()->json([
            'message' => 'تم تسجيل الحضور بنجاح',
            'booking' => $booking->fresh(),
        ], 200);
    }

    /**
     * Player manual check-in via confirmation code.
     */
    public function manual(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_code' => ['required', 'string'],
        ]);

        $booking = Booking::with('lobby')
            ->where('confirmation_code', strtoupper($validated['confirmation_code']))
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'كود التأكيد غير صالح'], 404);
        }

        if ($booking->status === 'completed') {
            return response()->json([
                'message' => 'تم تسجيل الحضور مسبقاً',
                'booking' => $booking,
            ], 200);
        }

        $dateStr = Carbon::parse($booking->lobby->match_date)->format('Y-m-d');
        $matchStart = Carbon::parse("{$dateStr} {$booking->lobby->start_time}");
        $windowStart = $matchStart->copy()->subMinutes(30);
        $windowEnd = $matchStart->copy()->addMinutes(60);

        if (now()->lt($windowStart) || now()->gt($windowEnd)) {
            return response()->json([
                'message' => 'تسجيل الحضور متاح فقط قبل الموعد بنصف ساعة وحتى ساعة بعد البداية',
            ], 422);
        }

        $booking->update(['status' => 'completed']);

        return response()->json([
            'message' => 'تم تسجيل الحضور بنجاح',
            'booking' => $booking->fresh(),
        ], 200);
    }
}
